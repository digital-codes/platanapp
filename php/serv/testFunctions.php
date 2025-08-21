<?php

// parse init
$iniPath = '/var/www/files/platane/config.ini';
//$iniPath = './config.ini';
if (!file_exists($iniPath)) {
    logError("Missing ini file: $iniPath", $logFile);
    http_response_code(500);
    echo json_encode(['error' => 'Configuration file not found']);
    exit;
}

$configLlm = parse_ini_file($iniPath, true)['LOCAL'] ?? null;
//$configLlm = parse_ini_file($iniPath, true)['REMOTE'] ?? null;
if (!$configLlm) {
    logError("Invalid config format in $iniPath", $logFile);
    http_response_code(500);
    echo json_encode(['error' => 'Invalid config format']);
    exit;
}

$fullPrompt = file('./functionsPrompt.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if ($fullPrompt === false) {
    logError("Failed to read prompt file", $logFile);
    http_response_code(500);
    echo json_encode(['error' => 'Failed to read prompt file']);
    exit;
}
//$fullPrompt = implode("\n", $fullPrompt) . PHP_EOL . "Nutze Tool Calls, wenn du Kontext brauchst." . PHP_EOL; // Convert array to string
$fullPrompt = implode("\n", $fullPrompt) . PHP_EOL; // Convert array to string


$apiKey = $configLlm["apiKey"];
$apiUrl = $configLlm["llurl"];
// Tool calling is not supported for model: microsoft/phi-4
//$model = $configLlm["llmodel"]; 
// model: ```json\n{\n  \"categories\": [\"Platanen\", \"Umweltschutz\"],\n  \"function_call\": {\n    \"name\": \"fetch_recent_environmental_news\",\n    \"arguments\": {\n      \"location\": \"Karlsruhe\"\n    }\n  }\n}\n```
$model = $configLlm["llmodel"];  // phi4- functions not supported
//$model = $configLlm["llmodel_alt1"]; // no function
//$model = $configLlm["llmodel_alt2"]; // tool call for wheather only.
//$model = $configLlm["llmodel_alt3"]; // works. only some tool calls. mistralai/Mistral-Small-3.2-24B-Instruct-2506
//$model = $configLlm["llmodel_alt4"];
//$model = $configLlm["llmodel_alt5"];
// model3 works in principle 

echo "Model: $model" . PHP_EOL;


// Example follow-up questions for the tree conversation
$folgefragen = [
    "Warum wurden die Platanen gefällt?",
    "Welche Bäume kommen danach?",
    "Wer hat das entschieden?",
    "Wer bist Du und wie geht es Dir?",
    "Wie ist das Wetter heute?",
    "Wieviel Sauerstoff erzeugt eine Platane?",
];

// Tool function definition
$tools = [[
    "type" => "function",
    "function" => [
        "name" => "function_call",
        "description" => "Gibt Kontext zur Frage basierend auf thematischen Kategorien.",
        "parameters" => [
            "type" => "object",
            "properties" => [
                "kategorien" => [
                    "type" => "array",
                    "items" => [
                        "type" => "string",
                        "enum" => [
                            "persönliches", "wetter", "umwelt", "pflanzenbiologie",
                            "stadtentwicklung", "politik", "umbau", "fällung"
                        ]
                    ],
                    "description" => "Thematische Kategorien."
                ]
            ],
            "required" => ["kategorien"]
        ]
    ]
]];

// System prompt
$messages = [[
    "role" => "system",
   	//"content" => $fullPrompt // "Du bist eine Platane in Karlsruhe. Du beantwortest Fragen über Stadtbäume, Umbau, Umwelt und KI. Verwende einfache Sprache. Nutze Tool Calls, wenn du Kontext brauchst."
   	"content" => $fullPrompt // "Du bist eine Platane in Karlsruhe. Du beantwortest Fragen über Stadtbäume, Umbau, Umwelt und KI. Verwende einfache Sprache. /* The line `Nutze immer Tool Calls, um zusätzlichen Kontext zu bekommen` is setting the system prompt content to encourage always using tool calls to get additional context in the conversation. This message is displayed to the user to prompt them to utilize tool calls for more detailed information or context related to the conversation topics. */
]];

$toolCallCount = 0;

// Loop through follow-up questions
foreach ($folgefragen as $frage) {
    echo "Frage: $frage
";
    $messages[] = ["role" => "user", "content" => $frage];

    $payload = [
        "model" => $model,
        "messages" => $messages,
        "tools" => $tools,
        "tool_choice" => "auto",
        "temperature" => 0.7
    ];

    $response = sendToOpenAI($apiUrl, $apiKey, $payload);
    $data = json_decode($response, true);
    //print_r($data);
    $assistantMsg = $data["choices"][0]["message"] ?? [];

    $antwort = handleToolCalls($messages, $assistantMsg, $model, $apiKey, $apiUrl);

    // Remove all <think>...</think> tags from the answer, if present
    if ($antwort) {
        // Remove all <think>...</think> tags from the answer, if present
        $antwort = preg_replace('/<think>.*?<\/think>/is', '', $antwort);
        $antwort = trim($antwort);
    }

    // Remove all emoji characters from the answer
    $antwort = preg_replace('/[\x{1F600}-\x{1F64F}|\x{1F300}-\x{1F5FF}|\x{1F680}-\x{1F6FF}|\x{1F1E0}-\x{1F1FF}|\x{2600}-\x{26FF}|\x{2700}-\x{27BF}|\x{1F900}-\x{1F9FF}|\x{1FA70}-\x{1FAFF}|\x{1F780}-\x{1F7FF}]/u', '', $antwort);


    if ($antwort) {
        echo "Antwort: $antwort\n";
    } else {
        echo "Keine Antwort erhalten.\n";
    }
}
echo "Tool Calls verwendet: $toolCallCount" . PHP_EOL;

// --- HELPER FUNCTIONS ---

function sendToOpenAI($url, $apiKey, $payload) {
    $headers = [
        "Authorization: Bearer $apiKey",
        "Content-Type: application/json"
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

function handleToolCalls(array &$messages, array $assistantMessage, string $model, string $apiKey, string $apiUrl, int $maxDepth = 5): ?string
{
    $depth = 0;
    global $toolCallCount; // Use global variable to count tool calls
    while ($depth++ < $maxDepth) {
        // print_r($messages);
        // If tool calls exist
        if (isset($assistantMessage["tool_calls"])) {
            $toolCalls = $assistantMessage["tool_calls"];
            // echo "Found " . count($toolCalls) . " tool call(s).\n";

            foreach ($toolCalls as $toolCall) {
                $toolCallCount++;
                // echo "Processing tool call: " . json_encode($toolCall) . "\n";

                $toolCallId = $toolCall["id"] ?? '';
                $functionName = $toolCall["function"]["name"] ?? '';
                $argumentsRaw = $toolCall["function"]["arguments"] ?? '{}';
                $arguments = json_decode($argumentsRaw, true);

                //if ($functionName === "function_call" && isset($arguments["kategorien"])) {
                if (isset($arguments["kategorien"])) {
                    $kontext = getContext($arguments["kategorien"]);

                    // Assistant tool call message
                    $messages[] = [
                        "role" => "assistant",
                        "tool_calls" => [$toolCall],
                        "content" => null
                    ];

                    // Tool response
                    $messages[] = [
                        "role" => "tool",
                        "tool_call_id" => $toolCallId,
                        "content" => json_encode(["kontext" => $kontext], JSON_UNESCAPED_UNICODE)
                    ];
                }
            }

            // Ask assistant again
            $nextPayload = [
                "model" => $model,
                "messages" => $messages,
                "temperature" => 0.7
            ];

            $nextResponse = sendToOpenAI($apiUrl, $apiKey, $nextPayload);
            $nextData = json_decode($nextResponse, true);
            //print_r($nextData);
            $assistantMessage = $nextData["choices"][0]["message"] ?? [];

            // If content, return answer
            if (!empty($assistantMessage["content"])) {
                $messages[] = ["role" => "assistant", "content" => $assistantMessage["content"]];
                return $assistantMessage["content"];
            }

            // If tool calls again, repeat
            if (isset($assistantMessage["tool_calls"])) {
                continue;
            }

            break;
        }

        // If no tool call, maybe content directly
        if (!empty($assistantMessage["content"])) {
            $messages[] = ["role" => "assistant", "content" => $assistantMessage["content"]];
            return $assistantMessage["content"];
        }

        break;
    }

    echo "Keine verwertbare Antwort.
";
    return null;
}

function getContext(array $kategorien): array {
    $kontext = [];
    foreach ($kategorien as $kategorie) {
        switch ($kategorie) {
            case "umbau":
                $kontext["umbau"] = "Die Kaiserstraße wird umgebaut – neue Leitungen, Oberfläche, Haltestellen.";
                break;
            case "fällung":
                $kontext["fällung"] = "Platanen mussten weichen, da ihre Wurzeln im Baufeld lagen.";
                break;
            case "politik":
                $kontext["politik"] = "Die Entscheidung wurde politisch getroffen, trotz Proteste der Bürger.";
                break;
            case "pflanzenbiologie":
                $kontext["pflanzenbiologie"] = "Zürgelbäume (Celtis australis) sind trockenresistent und gut geeignet für Städte.";
                break;
            case "stimmung":
                $kontext["stimmung"] = "Viele Bäume sind beunruhigt über die Fällung ihrer Nachbarn.";
                break;
            case "umwelt":
                $kontext["umwelt"] = "Weniger Bäume bedeuten schlechteres Stadtklima und Artenvielfalt.";
                break;
                case "wetter":
                $kontext["wetter"] = "Das Wetter heute ist sonnig mit Temperaturen um die 20 Grad Celsius.";
                break;
            case "stadtentwicklung":
                $kontext["stadtentwicklung"] = "Die Stadt Karlsruhe entwickelt sich weiter, Zürgelbäume werden neu gepflanzt.";
                break;
            case "unklar":
                $kontext["unklar"] = "ZU dieser Frage kann ich nichts sagen.";
            default:
                $kontext[$kategorie] = "Kein spezifischer Kontext verfügbar.";
        }
    }
    return $kontext;
}


