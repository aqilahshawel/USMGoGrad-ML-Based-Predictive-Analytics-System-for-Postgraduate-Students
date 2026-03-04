<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedule;
use Google\Cloud\Dialogflow\V2\Client\SessionsClient;
use Google\Cloud\Dialogflow\V2\DetectIntentRequest;
use Google\Cloud\Dialogflow\V2\QueryInput;
use Google\Cloud\Dialogflow\V2\TextInput;

class ChatbotController extends Controller
{
    /**
     * Display the chatbot view.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('chatbot');
    }

    /**
     * Handles sending a message to Dialogflow and returning the response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(Request $request)
    {
        $message = $request->input('message');
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
