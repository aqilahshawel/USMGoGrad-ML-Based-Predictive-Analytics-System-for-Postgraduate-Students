<?php

namespace App\Http\Controllers;

use App\Services\StudentAssistantService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Google\Cloud\Dialogflow\V2\Client\SessionsClient;
use Google\Cloud\Dialogflow\V2\DetectIntentRequest;
use Google\Cloud\Dialogflow\V2\QueryInput;
use Google\Cloud\Dialogflow\V2\TextInput;

class ChatbotController extends Controller
{
    public function __construct(private readonly StudentAssistantService $assistantService)
    {
    }

    /**
     * Handles sending a message to Dialogflow and returning the response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:3000'],
        ]);

        $message = trim((string) $validated['message']);

        if ($request->user()) {
            try {
                $assistantReply = $this->assistantService->tryHandle($request->user(), $message);

                if ($assistantReply !== null) {
                    $payload = ['reply' => $assistantReply];
                    if (Str::startsWith(trim($assistantReply), '<')) {
                        $payload['html'] = true;
                    }
                    return response()->json($payload);
                }
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        $projectId = env('DIALOGFLOW_PROJECT_ID');
        $sessionId = session()->getId();
        $sessionClient = new SessionsClient([
            'credentials' => config('services.dialogflow.credentials'),
        ]);
        $session = $sessionClient->sessionName($projectId, $sessionId);

        // Create the text input
        $textInput = new TextInput();
        $textInput->setText($message);
        $textInput->setLanguageCode('en-US');

        // Create the query input
        $queryInput = new QueryInput();
        $queryInput->setText($textInput);

        // Create the DetectIntentRequest
        $detectIntentRequest = new DetectIntentRequest();
        $detectIntentRequest->setSession($session);
        $detectIntentRequest->setQueryInput($queryInput);

        try {
            $response = $sessionClient->detectIntent($detectIntentRequest); // <-- Pass the request object

            $queryResult = $response->getQueryResult();
            $fulfillmentText = $queryResult->getFulfillmentText();

            $sessionClient->close();

            return response()->json(['reply' => $fulfillmentText]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error communicating with Dialogflow: ' . $e->getMessage()], 500);
        }
    }
}
