"""TF-IDF paper recommender (baseline).

I use this as a simple, explainable recommender:
- Input: student's cluster + a query (usually the student's research title)
- Data: a CSV catalog of papers (title/abstract/keywords/url)
- Output: top-K papers ranked by cosine similarity in TF-IDF space

This is not a neural model and there's no fine-tuning — it's classic information retrieval:
vectorize text with TF-IDF, then rank with Maximal Marginal Relevance (MMR) to keep
results relevant while reducing repetition inside each suggestion batch.
"""

import argparse
import csv
import json
import math
import sys
from dataclasses import dataclass
from typing import Dict, List, Optional, Tuple

from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity


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
    parser = argparse.ArgumentParser(description="TF-IDF paper recommender (baseline)")
    parser.add_argument("--catalog", required=True, help="Path to papers catalog CSV")
    parser.add_argument("--cluster", default="general", help="Student cluster")
    parser.add_argument("--query", required=True, help="Query text (e.g., student's research title)")
    parser.add_argument("--limit", type=int, default=3, help="Number of results")

    args = parser.parse_args(argv)

    papers = read_catalog(args.catalog)
    candidates = pick_candidates(papers, args.cluster)

    results = recommend(candidates, args.query, args.limit)
    sys.stdout.write(json.dumps(results, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
