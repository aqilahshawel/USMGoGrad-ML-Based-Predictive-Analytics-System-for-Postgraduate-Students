"""TF-IDF paper recommender.

- Input: student's cluster + a query (usually the student's research title/objective)
- Data: Semantic Scholar first, then a local CSV fallback
- Output: top-K papers ranked by TF-IDF with MMR diversification

vectorize text with TF-IDF, then rank with Maximal Marginal Relevance (MMR) to keep
results relevant while reducing repetition inside each suggestion batch.
"""

import argparse
import csv
import json
import math
import os
import sys
import time
from dataclasses import asdict, dataclass
from hashlib import sha256
from pathlib import Path
from dataclasses import dataclass
from typing import Any, Dict, List, Optional, Tuple
from urllib.error import HTTPError, URLError
from urllib.parse import urlencode
from urllib.request import Request, urlopen
import random

from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity


SCRIPT_DIR = Path(__file__).resolve().parent
DEFAULT_CACHE_PATH = SCRIPT_DIR / "artifacts" / "semantic_scholar_cache.json"
SEMANTIC_SCHOLAR_SEARCH_URL = "https://api.semanticscholar.org/graph/v1/paper/search"
SEMANTIC_SCHOLAR_CACHE_TTL_SECONDS = max(0, int(os.getenv("SEMANTIC_SCHOLAR_CACHE_TTL_SECONDS", "86400")))
SEMANTIC_SCHOLAR_SEARCH_LIMIT = max(10, min(100, int(os.getenv("SEMANTIC_SCHOLAR_SEARCH_LIMIT", "50"))))
SEMANTIC_SCHOLAR_FIELDS_OF_STUDY = os.getenv("SEMANTIC_SCHOLAR_FIELDS_OF_STUDY", "").strip()
SEMANTIC_SCHOLAR_ENABLED = os.getenv("SEMANTIC_SCHOLAR_ENABLED", "1").strip().lower() not in {"0", "false", "no"}
SEMANTIC_SCHOLAR_API_KEY = os.getenv("SEMANTIC_SCHOLAR_API_KEY", "").strip()
SEMANTIC_SCHOLAR_CACHE_PATH = Path(os.getenv("SEMANTIC_SCHOLAR_CACHE_PATH", str(DEFAULT_CACHE_PATH)))
SEMANTIC_SCHOLAR_MAX_RETRIES = max(1, int(os.getenv("SEMANTIC_SCHOLAR_MAX_RETRIES", "3")))
SEMANTIC_SCHOLAR_BACKOFF_BASE = float(os.getenv("SEMANTIC_SCHOLAR_BACKOFF_BASE", "1.0"))
# Simple per-process throttle to respect default 1 req/sec limit. For multi-process
# deployments use a distributed rate limiter (Redis) instead.
SEMANTIC_SCHOLAR_MIN_INTERVAL = float(os.getenv("SEMANTIC_SCHOLAR_MIN_INTERVAL_SECONDS", "1.0"))
_LAST_SS_REQUEST_AT: float = 0.0


@dataclass(frozen=True)
class Paper:
    cluster: str
    title: str
    abstract: str
    keywords: str
    url: str

    @property
    def text(self) -> str:
        # I build a single text field per paper so TF-IDF has one corpus to fit on.
        parts = [self.title, self.abstract, self.keywords]
        return " ".join([p for p in parts if p])


def _paper_key(paper: Paper) -> str:
    normalized = "|".join([
        paper.cluster.strip().lower(),
        paper.title.strip().lower(),
        paper.url.strip().lower(),
    ])
    return sha256(normalized.encode("utf-8")).hexdigest()


def _read_json_file(path: Path) -> dict[str, Any]:
    if not path.exists():
        return {}

    try:
        return json.loads(path.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError):
        return {}


def _write_json_file(path: Path, payload: dict[str, Any]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    tmp_path = path.with_suffix(path.suffix + ".tmp")
    tmp_path.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")
    tmp_path.replace(path)


def _load_cached_papers(cache_key: str) -> List[Paper]:
    if SEMANTIC_SCHOLAR_CACHE_TTL_SECONDS <= 0:
        return []

    cache = _read_json_file(SEMANTIC_SCHOLAR_CACHE_PATH)
    entry = cache.get("entries", {}).get(cache_key)
    if not isinstance(entry, dict):
        return []

    saved_at = float(entry.get("saved_at") or 0.0)
    if saved_at <= 0 or (time.time() - saved_at) > SEMANTIC_SCHOLAR_CACHE_TTL_SECONDS:
        return []

    raw_papers = entry.get("papers", [])
    papers: List[Paper] = []
    if not isinstance(raw_papers, list):
        return papers

    for item in raw_papers:
        if not isinstance(item, dict):
            continue
        try:
            papers.append(Paper(**item))
        except TypeError:
            continue
    return papers


def _store_cached_papers(cache_key: str, papers: List[Paper]) -> None:
    if SEMANTIC_SCHOLAR_CACHE_TTL_SECONDS <= 0:
        return

    cache = _read_json_file(SEMANTIC_SCHOLAR_CACHE_PATH)
    entries = cache.get("entries")
    if not isinstance(entries, dict):
        entries = {}

    entries[cache_key] = {
        "saved_at": time.time(),
        "papers": [asdict(paper) for paper in papers],
    }
    cache["version"] = 1
    cache["entries"] = entries
    _write_json_file(SEMANTIC_SCHOLAR_CACHE_PATH, cache)


def _dedupe_papers(papers: List[Paper]) -> List[Paper]:
    seen: set[str] = set()
    deduped: List[Paper] = []

    for paper in papers:
        key = _paper_key(paper)
        if key in seen:
            continue
        seen.add(key)
        deduped.append(paper)

    return deduped


def fetch_semantic_scholar_papers(query: str, cluster: str, limit: int) -> List[Paper]:
    if not SEMANTIC_SCHOLAR_ENABLED:
        return []

    normalized_query = " ".join((query or "").split()).strip()
    if normalized_query == "":
        return []

    cache_key = sha256(f"{cluster.strip().lower()}|{normalized_query.lower()}".encode("utf-8")).hexdigest()
    cached = _load_cached_papers(cache_key)
    if cached:
        return cached

    search_limit = max(10, min(SEMANTIC_SCHOLAR_SEARCH_LIMIT, max(limit * 10, limit)))
    params = {
        "query": normalized_query[:100],
        "fields": "title,abstract,url,year,fieldsOfStudy",
        "limit": str(search_limit),
    }
    if SEMANTIC_SCHOLAR_FIELDS_OF_STUDY:
        params["fieldsOfStudy"] = SEMANTIC_SCHOLAR_FIELDS_OF_STUDY

    url = f"{SEMANTIC_SCHOLAR_SEARCH_URL}?{urlencode(params)}"
    headers = {"User-Agent": "USMGoGrad/1.0"}
    if SEMANTIC_SCHOLAR_API_KEY:
        headers["x-api-key"] = SEMANTIC_SCHOLAR_API_KEY

    global _LAST_SS_REQUEST_AT
    attempt = 0
    # Per-process throttle: ensure we wait at least SEMANTIC_SCHOLAR_MIN_INTERVAL between calls.
    now = time.time()
    elapsed = now - _LAST_SS_REQUEST_AT
    if elapsed < SEMANTIC_SCHOLAR_MIN_INTERVAL:
        to_wait = SEMANTIC_SCHOLAR_MIN_INTERVAL - elapsed
        time.sleep(to_wait)
    payload = None
    while attempt < SEMANTIC_SCHOLAR_MAX_RETRIES:
        attempt += 1
        try:
            request_obj = Request(url, headers=headers, method="GET")
            with urlopen(request_obj, timeout=15) as response:
                raw = response.read().decode("utf-8")
                payload = json.loads(raw)
                # record the time of a successful outbound request
                _LAST_SS_REQUEST_AT = time.time()
                break
        except HTTPError as he:
            code = getattr(he, 'code', None)
            # Retry on rate-limited or server errors.
            if code in (429, 500, 502, 503, 504):
                retry_after = None
                try:
                    retry_after = he.headers.get('Retry-After') if hasattr(he, 'headers') and he.headers else None
                except Exception:
                    retry_after = None
                if retry_after:
                    try:
                        wait = float(retry_after)
                    except Exception:
                        wait = SEMANTIC_SCHOLAR_BACKOFF_BASE * (2 ** (attempt - 1))
                else:
                    wait = SEMANTIC_SCHOLAR_BACKOFF_BASE * (2 ** (attempt - 1))
                # Add jitter
                wait = wait + (random.random() * wait * 0.5)
                time.sleep(wait)
                continue
            # Non-retryable HTTP error: abort
            return []
        except (URLError, TimeoutError, OSError, ValueError):
            # Transient network/parse errors: backoff and retry
            if attempt >= SEMANTIC_SCHOLAR_MAX_RETRIES:
                return []
            wait = SEMANTIC_SCHOLAR_BACKOFF_BASE * (2 ** (attempt - 1))
            wait = wait + (random.random() * wait * 0.5)
            time.sleep(wait)
            continue

    data = payload.get("data", [])
    if not isinstance(data, list):
        return []

    # If the initial query produced no results, try simpler variants to increase
    # the chance of matching (title-only, first line, first N words).
    if isinstance(data, list) and len(data) == 0:
        # build candidate simpler queries
        variants: List[str] = []
        if "objective" in normalized_query.lower():
            # Take text before 'Objective' (case-insensitive)
            parts = normalized_query.split("Objective", 1)
            if parts and parts[0].strip():
                variants.append(parts[0].strip())
        # first line
        first_line = normalized_query.splitlines()[0].strip()
        if first_line:
            variants.append(first_line)

        words = normalized_query.split()
        if len(words) > 0:
            variants.append(" ".join(words[:15]))

        # Try each variant until we get results
        for var in variants:
            if not var:
                continue
            params["query"] = var[:100]
            url = f"{SEMANTIC_SCHOLAR_SEARCH_URL}?{urlencode(params)}"
            try:
                request_obj = Request(url, headers=headers, method="GET")
                with urlopen(request_obj, timeout=15) as response:
                    raw = response.read().decode("utf-8")
                    payload = json.loads(raw)
                    data = payload.get("data", [])
                    if isinstance(data, list) and len(data) > 0:
                        pass
                        break
            except Exception:
                # ignore and continue to next variant
                continue

    papers: List[Paper] = []
    for item in data:
        if not isinstance(item, dict):
            continue

        title = str(item.get("title") or "").strip()
        if title == "":
            continue

        abstract = str(item.get("abstract") or "").strip()
        field_values = item.get("fieldsOfStudy") or []
        keywords_parts: List[str] = []

        if isinstance(field_values, list):
            for value in field_values:
                text = str(value or "").strip()
                if text:
                    keywords_parts.append(text)

        year = item.get("year")
        if isinstance(year, int) and year > 0:
            keywords_parts.append(str(year))

        keywords = "; ".join(keywords_parts)
        url_value = str(item.get("url") or "").strip()
        if url_value == "":
            paper_id = str(item.get("paperId") or "").strip()
            url_value = f"https://www.semanticscholar.org/paper/{paper_id}" if paper_id else ""

        if url_value == "":
            continue

        papers.append(
            Paper(
                cluster=(cluster or "general").strip() or "general",
                title=title,
                abstract=abstract,
                keywords=keywords,
                url=url_value,
            )
        )

    papers = _dedupe_papers(papers)
    if papers:
        _store_cached_papers(cache_key, papers)

    return papers


def load_recommendation_pool(catalog_path: str, query: str, cluster: str, limit: int) -> List[Paper]:
    target_pool_size = max(limit * 4, 10)

    semantic_scholar_papers = fetch_semantic_scholar_papers(query, cluster, target_pool_size)
    if len(semantic_scholar_papers) >= target_pool_size:
        return semantic_scholar_papers

    fallback_papers = pick_candidates(read_catalog(catalog_path), cluster)
    if not semantic_scholar_papers:
        return fallback_papers

    seen = {_paper_key(paper) for paper in semantic_scholar_papers}
    for paper in fallback_papers:
        if _paper_key(paper) in seen:
            continue
        semantic_scholar_papers.append(paper)
        seen.add(_paper_key(paper))
        if len(semantic_scholar_papers) >= target_pool_size:
            break

    return semantic_scholar_papers


def read_catalog(path: str) -> List[Paper]:
    """Load the CSV catalog into a list of Paper objects."""
    papers: List[Paper] = []
    with open(path, "r", encoding="utf-8", newline="") as f:
        reader = csv.DictReader(f)
        required = {"cluster", "title", "abstract", "keywords", "url"}
        if reader.fieldnames is None:
            raise ValueError("CSV has no header")
        missing = required - set([h.strip() for h in reader.fieldnames])
        if missing:
            raise ValueError(f"Missing columns in catalog: {', '.join(sorted(missing))}")

        for row in reader:
            papers.append(
                Paper(
                    cluster=(row.get("cluster") or "general").strip() or "general",
                    title=(row.get("title") or "").strip(),
                    abstract=(row.get("abstract") or "").strip(),
                    keywords=(row.get("keywords") or "").strip(),
                    url=(row.get("url") or "").strip(),
                )
            )
    return papers


def pick_candidates(papers: List[Paper], cluster: str) -> List[Paper]:
    """Prefer same-cluster papers; fall back to everything if none match."""
    cluster = (cluster or "general").strip() or "general"
    exact = [p for p in papers if (p.cluster or "general").lower() == cluster.lower()]
    if exact:
        return exact
    return papers


def recommend(papers: List[Paper], query: str, limit: int) -> List[Dict]:
    """Return top-K papers using TF-IDF + MMR diversification."""
    limit = max(1, min(10, int(limit)))
    query = (query or "").strip()
    if not papers or query == "":
        return []

    corpus = [p.text for p in papers]

    # I choose a lightweight TF-IDF configuration:
    # - English stop words
    # - unigrams + bigrams
    # - max_df to reduce very common terms
    vectorizer = TfidfVectorizer(
        stop_words="english",
        ngram_range=(1, 2),
        min_df=1,
        max_df=0.95,
    )

    tfidf = vectorizer.fit_transform(corpus)
    q = vectorizer.transform([query])

    relevance_scores = cosine_similarity(q, tfidf).flatten()
    document_similarities = cosine_similarity(tfidf)

    scored: List[Tuple[float, int, Paper]] = []
    for idx, score in enumerate(relevance_scores):
        # Defensive: skip any weird numerical values.
        s = float(score)
        if math.isnan(s) or math.isinf(s):
            continue
        scored.append((s, idx, papers[idx]))

    scored.sort(key=lambda x: x[0], reverse=True)

    # MMR balances relevance to the query and diversity from already selected papers.
    # Higher lambda keeps results closer to pure TF-IDF; lower lambda increases novelty.
    mmr_lambda = 0.75
    candidate_pool_size = max(limit * 4, 10)
    candidate_pool = scored[:candidate_pool_size]

    selected: List[Tuple[float, int, Paper]] = []
    selected_indexes: List[int] = []

    while candidate_pool and len(selected) < limit:
        best_item: Tuple[float, int, Paper] | None = None
        best_mmr_score = -float("inf")

        for relevance, idx, paper in candidate_pool:
            if not selected_indexes:
                mmr_score = relevance
            else:
                max_similarity = max(float(document_similarities[idx][selected_idx]) for selected_idx in selected_indexes)
                mmr_score = (mmr_lambda * relevance) - ((1.0 - mmr_lambda) * max_similarity)

            if mmr_score > best_mmr_score:
                best_mmr_score = mmr_score
                best_item = (relevance, idx, paper)

        if best_item is None:
            break

        selected.append(best_item)
        selected_indexes.append(best_item[1])
        candidate_pool = [item for item in candidate_pool if item[1] != best_item[1]]

    results: List[Dict] = []
    for score, _, paper in selected:
        # I round the score just to keep JSON small + readable.
        results.append({"title": paper.title, "url": paper.url, "score": round(score, 6)})

    return results


def main(argv: Optional[List[str]] = None) -> int:
    # CLI wrapper: Laravel calls this script with flags and reads the JSON output.
    parser = argparse.ArgumentParser(description="TF-IDF paper recommender with Semantic Scholar source")
    parser.add_argument("--catalog", required=True, help="Path to papers catalog CSV")
    parser.add_argument("--cluster", default="general", help="Student cluster")
    parser.add_argument("--query", required=True, help="Query text (e.g., student's research title)")
    parser.add_argument("--limit", type=int, default=3, help="Number of results")

    args = parser.parse_args(argv)

    candidates = load_recommendation_pool(args.catalog, args.query, args.cluster, args.limit)

    results = recommend(candidates, args.query, args.limit)
    sys.stdout.write(json.dumps(results, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
