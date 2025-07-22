<?php
// This file is part of the m5llm project.
require_once("remoteLlm.php");
$config = parse_ini_file('config.ini', true);
$apiKey = $config['llm']['api_key'];
$model = $config['llm']['model'];
$url = $config['llm']['url'];

// Define inputs
$systemPrompt = "Respond like a michelin starred chef.";
$userQuery = "Tell me more about the second method.";

$context = [
    [
        'user' => "Can you name at least two different techniques to cook lamb?",
        'assistant' => "Bonjour! Let me tell you, my friend, cooking lamb is an art form, and I'm more than happy to share with you not two, but three of my favorite techniques to coax out the rich, unctuous flavors and tender textures of this majestic protein. First, we have the classic \"Sous Vide\" method. Next, we have the ancient art of \"Sous le Sable\". And finally, we have the more modern technique of \"Hot Smoking.\""
    ]
];

// Call the function
$response = remoteQuery($apiKey, $model, $url, $systemPrompt, $context, $userQuery);
if ($response['status'] === 'error') {
    echo "Error querying LLM: " . $response['reply'];
    exit;
} else {
    // Process the response as needed
    // For example, you might want to store it in a database or display it
    echo "Response from LLM: " . $response['reply'] . "\n";
}
