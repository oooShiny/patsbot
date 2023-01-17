<?php
// Load composer libraries.
require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Psr7\Request;
use SleekDB\Store;
use GuzzleHttp\Client;

// Change for testing locally.
$server = 'sports-community.ddev.site';

// Get a list of all games currently happening today.
//$live_games = json_decode(file_get_contents('https://sp0rts.fans/api/live-games'), TRUE);
$live_games = json_decode(file_get_contents('https://'.$server.'/api/live-games'), TRUE);

// Don't bother running anything if there are no live games.
if (empty($live_games)) {
  return;
}

// Database setup.
$databaseDirectory = __DIR__ . '/lastPlayDB';
$db = new Store('plays', $databaseDirectory);

// Get environment variables from .env file.
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();
// Pusher setup.
$pusher = new Pusher\Pusher(
  $_ENV['PUSHER_KEY'],
  $_ENV['PUSHER_SECRET'],
  $_ENV['PUSHER_ID'],
  ['cluster' => 'us2']
);


// Set a while loop to run 5 times with 10 second sleeps (50 seconds).
$count = 1;
while ($count !== 6) {

  foreach ($live_games as $game) {
    // Get game/play data from ESPN endpoint.
    $game_data = json_decode(file_get_contents($game['endpoint_url']), TRUE);
    $plays = $game_data['items'];
    $latest_play = end($plays);

    // Check if the latest play has been sent to the website yet.
    $db_game = $db->findOneBy(['game_id', '=', $game['id']]);

    if (is_null($db_game)) {
      // Send data to pusher to update the node.
      send_comment($latest_play, $pusher, $game['nid'], $game);

      // Insert play into the database.
      $db_play = $db->insert([
        'game_id' => $game['id'],
        'last_play' => $latest_play['id']
      ]);
    }
    elseif ($db_game['last_play'] !== $latest_play['id']) {
      // Send data to pusher to update the node, and send it to Drupal so it
      // gets saved in the database as well.
      send_comment($latest_play, $pusher, $game['nid'], $game);
      post_drupal_comment($latest_play, $game, $server);

      // Track this play in the local database.
      $db_game['last_play'] = $latest_play['id'];
      $db_play = $db->update($db_game);
    }
  }
  // Pause for 10 seconds, so we're not hammering the ESPN API.
  $count++;
  sleep(10);
}

/**
 * Send the play as a comment via Pusher.com so the page updates automatically.
 */
function send_comment($latest_play, $pusher, $nid, $game) {
  $comment = [
    'play_scored' => $latest_play['scoringPlay'],
    'play_title' => $latest_play['text'],
    'play_quarter' => $latest_play['period']['number'],
    'play_time' => $latest_play['clock']['displayValue'],
    'play_t1_logo' => $game['field_team_1_logo'],
    'play_t2_logo' => $game['field_team_2_logo'],
    'play_score' => $latest_play['homeScore'] . " - " . $latest_play['awayScore'],
    'play_down' => ordinal($latest_play['start']['down']),
    'play_distance' => $latest_play['start']['distance']
  ];
  $event = 'new_play_' . $nid;
  $pusher->trigger(
    'sp0rts-comments',
    $event,
    $comment
    );
  var_dump($comment);
}

/**
 * Post the current play as a Drupal comment so it is saved in the database.
 */
function post_drupal_comment($latest_play, $game, $server) {
  $down = ordinal($latest_play['start']['down']);
  $play_body = <<<EOT
<h4>{$latest_play['text']}</h4>
<p>
  <span class='play-quarter border p-1'>Q {$latest_play['period']['number']}</span>
  <span class='play-time border p-1'>{$latest_play['clock']['displayValue']}</span>
</p>
<div class='flex gap-10 justify-center'>
    <img class='h-20' src='{$game['field_team_1_logo']}'>
    <span class='play-score text-5xl'>{$latest_play['homeScore']} - {$latest_play['awayScore']}</span>
    <img class='h-20' src='{$game['field_team_2_logo']}'>
</div>
<p>
    <span class='play-down'>{$down}</span> and <span class='play-distance'>{$latest_play['start']['distance']}</span>
</p>
EOT;

  $play_comment = [
    "data" => [
      "type" => "comment",
      "attributes" => [
        "subject" => substr($latest_play['shortText'], 0, 64),
        "entity_type" => "node",
        "field_name" => "comment",
        "comment_body" => [
          "value" => $play_body,
          "format" => "basic_html"
        ]
      ],
      "relationships" => [
        "comment_type" => [
          "data" => [
            "type" => "comment_type",
            "id" => "9cff1ee6-35e2-4315-a9c9-142f386f318b"
          ]
        ],
        "entity_id" => [
          "data" => [
            "type" => "node--game",
            "id" => $game['id'],
          ]
        ]
      ]
    ]
  ];

  $client = new Client();
  $headers = [
    'Accept' => 'application/vnd.api+json',
    'Content-Type' => 'application/vnd.api+json',
    'Authorization' => 'Basic ' . $_ENV['DEV_SHA'],
  ];
  $body = json_encode($play_comment);
  $request = new Request('POST', 'https://'.$server.'/jsonapi/comment', $headers, $body);
  $res = $client->sendAsync($request)->wait();
//  echo $res->getBody();

}

/**
 * If the game has ended, update the game node.
 */
function game_ended($game, $server) {
  $serialized_entity = json_encode([
    'field_game_has_ended' => [['value' => '1']],
    'type' => [['target_id' => 'game']],
    '_links' => ['type' => [
      'href' => 'https://'.$server.'/rest/type/node/game'
    ]],
  ]);

  $client = new Client();
  $headers = [
    'Accept' => 'application/vnd.api+json',
    'Content-Type' => 'application/vnd.api+json',
    'Authorization' => 'Basic ' . $_ENV['DEV_SHA'],
  ];
  $body = json_encode($serialized_entity);
  $request = new Request('PATCH', 'https://'.$server.'/jsonapi/comment', $headers, $body);
  $res = $client->sendAsync($request)->wait();
  echo $res->getBody();
}

/**
 * Add an ordinal to a number (1st, 2nd, 3rd, etc).
 */
function ordinal($num) {
  $ones = $num % 10;
  $tens = floor($num / 10) % 10;
  if ($tens == 1) {
    $suff = "th";
  } else {
    switch ($ones) {
      case 1 : $suff = "st"; break;
      case 2 : $suff = "nd"; break;
      case 3 : $suff = "rd"; break;
      default : $suff = "th";
    }
  }
  return $num . $suff;
}
