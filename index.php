<form>
    <label for="season">Season:</label>
        <input id="season" name="season" type="text" value=<?php print $_GET['season']; ?>>
    <label for="seasontype">Game Type:</label>
        <select id="seasontype" name="seasontype">
            <option value="1">Preseason</option>
            <option value="2">Regular Season</option>
            <option value="3">Postseason</option>
        </select>
    <label for="week">Game Type:</label>
        <select id="week" name="week">
            <?php for ($x = 1; $x < 18; $x++): ?>
                <option value="<?php print $x; ?>">Week <?php print $x; ?></option>
            <?php endfor; ?>
        </select>
    <input type="submit" value="Submit">
    <a href="/">Reset</a>
</form>

<?php

$espn_url = 'http://site.api.espn.com/apis/site/v2/sports/football/nfl/scoreboard';
$args['dates'] = $_REQUEST['season'] ?? '';
$args['seasontype'] = $_REQUEST['seasontype'] ?? '';
$args['week'] = $_REQUEST['week'] ?? '';
if (!empty($args['dates']) || !empty($args['seasontype']) || !empty($args['week'])) {
    $espn_url .= '?';
    foreach($args as $name => $value) {
        $espn_url .= $name . '=' . $value . '&';
    }
}
var_dump($espn_url);
$json = file_get_contents($espn_url);
$games = json_decode($json, true);
$pats_games = [];
$season = $games['season']['year'];
$week = $games['week']['number'];

// Get the Patriots game from the list of games.
foreach ($games['events'] as $game) {
    $thisgame = [];
    $is_pats_game = false;
    /** 
     * Game type:
     * 1 -> preseason
     * 2 -> regular season
     * 3 -> postseason
     **/
    $game_type = $game['season']['type'];

    if (isset($game['weather']['highTemperature'])) {
        $temp = $game['weather']['highTemperature'] . '°, ';
    }
    elseif (isset($game['weather']['temperature'])) {
        $temp = $game['weather']['temperature'] . '°, ';
    }
    else {
        $temp = '';
    }
    $weather = $temp . $game['weather']['displayValue'];
    $time = explode(' - ', $game['status']['type']['shortDetail']);
    
    // Get the general game details.
    $thisgame[$game['id']] = [
        'home' => [],
        'away' => [],
        'status' => $game['status'],
        'venue' => $game['competitions'][0]['venue'],
        'favorite' => $game['competitions'][0]['odds'][0]['details'],
        'ou' => $game['competitions'][0]['odds'][0]['overUnder'],
        'type' => $game_type,
        'weather' => $weather,
        'time' => $time[1],
        'date' => $game['date'],
    ];
    
    if (strpos($game['status']['type']['detail'], 'Final') !== FALSE) {
        $thisgame[$game['id']]['leaders'] = $game['leaders'];
    }

    // Get team details.
    foreach ($game['competitions'][0]['competitors'] as $team) {
        if ($team['id'] == 17) { 
            $is_pats_game = true;
        }
        $score = $team['score'];
        $box = $team['linescores'];
        $ha = $team['homeAway'];
        $record = $team['records'][0]['summary'];

        $team = $team['team'];
        $thisgame[$game['id']][$ha] = [
            'location' => $team['location'],
            'name' => $team['name'],
            'display' => $team['displayName'],
            'score' => $score,
            'box' => $box,
            'record' => $record
        ];
    }
    if ($is_pats_game) {
        $pats_games = $thisgame;
    }
}
// Get highlights from JSON file.
$today = date('Ymd');
$file = file_get_contents('gifdata.json');
$hl_array = json_decode($file, true);
$highlights = [];
foreach ($hl_array as $date => $hls) {
    if ($date == $today) {
        asort($hls, SORT_NUMERIC);
        foreach ($hls as $hl) {
            $highlights[] = preg_replace('#^\d+#', '', $hl);
        }
    }
}

// Print out game info.
foreach ($pats_games as $game) {
    $game_status = $game['status']['type']['detail'];
    $home_team = $game['home']['display'];
    $away_team = $game['away']['display'];
    // Format the title based on the season status.
    if ($game_type == 1) {
        $season_week .= $season . ' Preseason Week ' . $week;
    }
    else {
        $season_week .= $season . ' Week ' . $week;
    }

    // Format title for pre/post game.
    if (strpos($game_status, 'Final') !== FALSE) {
        $post_title = 'Official Post-Game Thread: ';
        if ($game['away']['score'] > $game['home']['score']) {
            $post_title .= $away_team . ' defeat ' . $home_team;
            $post_title .= ' ' . $game['away']['score'] . ' - ' . $game['home']['score'];
        }
        else {
            $post_title .= $home_team . ' defeat ' . $away_team;
            $post_title .= ' ' . $game['home']['score'] . ' - ' . $game['away']['score'];
        }
        
    }
    else {
        $post_title = 'Official Game Day Thread: ';
        $post_title .= $away_team . ' (' . $game['away']['record'] . ')';  
        $post_title .= ' @ '; 
        $post_title .= $home_team . ' (' . $game['home']['record'] . ')'; 
        $post_title .= ' [kickoff ' . $game['time'] . ']';
    }

    // Build Post.
    $post = '';

    // Season & Week
    $post .= '#' . $season_week . "\n --- \n";

    // Teams
    $post .= '# [' . $away_team . '](' . get_subreddit_link($away_team) . '#away)';
    if (strpos($game_status, 'Final') === FALSE) {
        $post .= ' (' . $game['away']['record'] . ')'; 
    } 
    $post .= ' at ';
    $post .= '[' . $home_team . '](' . get_subreddit_link($home_team) . '#home)'; 
    if (strpos($game_status, 'Final') === FALSE) {
        $post .= ' (' . $game['home']['record'] . ')'; 
    }  
    $post .= "\n";

    // Stadium & Location
    $post .= $game['venue']['fullName'] . ' in ' . $game['venue']['address']['city'] . ', ' . $game['venue']['address']['state'];
    $post .= "\n\n";

    // Box Score
    if (strpos($game_status, 'Final') !== FALSE) {
        $post .= '## Box Score';
        $post .= "\n\n";
        if (count($game['away']['box']) > 4) {
            $post .= 'Team | 1 | 2 | 3 | 4 | OT | Final' . "\n";
        }
        else {
            $post .= 'Team | 1 | 2 | 3 | 4 | Final' . "\n";
        }
        $post .= '---|---|---|---|---|---' . "\n";
        $post .= $away_team . ' | '; foreach ($game['away']['box'] as $q) { $post .= $q['value'] . ' | '; } $post .= $game['away']['score'];  
        $post .= "\n";
        $post .= $home_team . ' | '; foreach ($game['home']['box'] as $q) { $post .= $q['value'] . ' | '; } $post .= $game['home']['score'];
        $post .= "\n\n";
    } 
    else {
        // Game Date
        $post .= '## ' . $game_status;
        $post .= "\n\n";
    }

    // Odds
    if (strpos($game_status, 'Final') === FALSE) { 
        $post .= '* **Favorite:** ' . $game['favorite'];
        $post .= "\n";
        $post .= '* **Over/Under:** ' . $game['ou'];
        $post .= "\n";
        $post .= '* **Weather:** ' . $game['weather'];
        $post .= "\n\n";
    }

    // Highlights
    if (!empty($highlights)) {
        $post .= '## Highlights' . "\n";
        $post .= '*Courtesy of u/timnog*' . "\n\n";
        foreach ($highlights as $h) {
            $post .= '1. ' . $h . "\n";
        }
        $post .= "\n\n";
    } 


    $post .= '---' . "\n";
    $post .= '## Game Thread Notes' . "\n";
    $post .= '* Discuss whatever you wish. You can trash talk, but keep it civil.' . "\n";
    $post .= '* If you are experiencing problems with comment sorting in the official reddit app, we suggest using a third-party client instead ([Android](/r/Android/comments/f8tg1x/which_reddit_app_do_you_use_and_why_2020_edition/), [iOS](/r/applehelp/comments/a6pzha/best_reddit_app_for_ios/)).' . "\n";
    $post .= '* Turning comment sort to \'new\' will help you see the newest comments.' . "\n";
    

} // endforeach;

// Display the post on the screen.
print '<h2>' . $post_title . '</h2>';

print nl2br($post);

time_to_post($game, $post, $post_title);
?>
<!-- Post this as a test to nflgifbot subreddit. -->
<form>
    <input type="submit" value="Test Post">
    <input type="hidden" name="test_post">
</form>

<?php
if (isset($_REQUEST['test_post'])) {
    post_to_reddit($post_title, $post);
}

/**
 * Check if it's time to post the thread to Reddit.
 * This runs on cron.
 */
function time_to_post($game, $post, $post_title) {

    // Get the postdata file to see if we've already posted this.
    $file = file_get_contents('postdata.json');
    $old_posts = json_decode($file, true);

    $final = strpos($game['status']['type']['detail'], 'Final');

    date_default_timezone_set('America/New_York');
    $current_time = time();
    $game_time = strtotime($game['date']);
    $post_time = $game_time - 3600; // Pregame post goes up an hour before game time.
    $diff = $current_time - $post_time;
    
    // If the game isn't finished and diff is less than 60 seconds apart,
    // then this game hasn't started yet.
    if (($final === FALSE) && (0 > $diff && $diff > -60)) {
        if (array_key_exists($game_time . '-pre', $old_posts)) {
            // We already posted, do nothing.
        }
        else {
            $post .= 'Now: ' . $current_time . "\n" . 'Gametime: ' . $game_time . "\n" . 'Post time: ' . $post_time;
            post_to_reddit($post_title, $post);
            $old_posts[$game_time . '-pre'] = $post_title;
            $data = json_encode($old_posts, JSON_PRETTY_PRINT);
            file_put_contents('postdata.json', $data);
        }   
    }
    // If the game is over, post if we haven't posted yet.
    elseif ($final !== FALSE) {
        if (array_key_exists($game_time . '-post', $old_posts)) {
            // We already posted, do nothing.
        }
        else {
            $post .= 'Now: ' . $current_time . "\n" . 'Gametime: ' . $game_time . "\n" . 'Post time: ' . $post_time;
            post_to_reddit($post_title, $post);
            $old_posts[$game_time . '-post'] = $post_title;
            $data = json_encode($old_posts, JSON_PRETTY_PRINT);
            file_put_contents('postdata.json', $data);
        }  
    }
}

/**
 * Post the message to Reddit.
 */
function post_to_reddit($post_title, $post) {
    $reddit_info = get_params();
    $auth = reddit_auth($reddit_info);

    $post_data = [
        'title' => $post_title,
        'sr' => 'patriots',
        'kind' => 'self',
        'text' => $post,
        'sendreplies' => 'false'
    ];

    $response = do_curl('submit', 'POST', $post_data, $auth);
}

/**
 * Authenticate with Reddit.
 */
function reddit_auth($reddit_info) {
    // Post paramaters.
    $params = array(
        'grant_type' => 'password',
        'username' => $reddit_info['REDDIT_USER'],
        'password' => $reddit_info['REDDIT_PASS']
    );

    // curl settings and call to reddit
    $ch = curl_init( 'https://www.reddit.com/api/v1/access_token' );
    curl_setopt($ch, CURLOPT_USERPWD, $reddit_info['CLIENT_ID'] . ':' . $reddit_info['CLIENT_SECRET']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

    // curl response from reddit
    $response_raw = curl_exec($ch);
    $response = json_decode($response_raw, TRUE);
    curl_close($ch);
    return $response;
}

/**
 * Get Reddit params from the .env file.
 */ 
function get_params() {
    $file = file_get_contents('../.env');
    $array = explode("\n", $file);
    $params = [];
    foreach ($array as $param) {
        $p = explode('=', $param);
        $params[$p[0]] = $p[1];
    }
    return $params;
}

function do_curl($action, $method, $params, $auth, $json = FALSE) {
    $ch = curl_init('https://oauth.reddit.com/api/' . $action);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'PatsBot by /u/' . $username);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: " . $auth['token_type'] . " " . $auth['access_token']]);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    if ($json) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, 'Content-Type: application/json');
    }
    // curl response from our post call
    $response_raw = curl_exec($ch);
    $err = curl_error($ch);
    $response = json_decode($response_raw, TRUE);
    curl_close($ch);
    if ($err) {
        return $err;
    }
    else {
        return $response;
    }
}

/**
 * Get team subreddit from team name.
 */
function get_subreddit_link($team) {
    $subreddits = [
        'Ravens' => '/r/ravens',
        'Bengals' => '/r/bengals',
        'Browns' => '/r/browns',
        'Steelers' => '/r/steelers',
        'Texans' => '/r/texans',
        'Colts' => '/r/colts',
        'Jaguars' => '/r/jaguars',
        'Titans' => '/r/tennesseetitans',
        'Bills' => '/r/buffalobills',
        'Dolphins' => '/r/miamidolphins',
        'Patriots' => '/r/patriots',
        'Jets' => '/r/nyjets',
        'Broncos' => '/r/denverbroncos',
        'Chiefs' => '/r/kansascitychiefs',
        'Raiders' => '/r/raiders',
        'Chargers' => '/r/chargers',
        'Bears' => '/r/chibears',
        'Lions' => '/r/detroitlions',
        'Packers' => '/r/greenbaypackers',
        'Vikings' => '/r/minnesotavikings',
        'Falcons' => '/r/falcons',
        'Panthers' => '/r/panthers',
        'Saints' => '/r/saints',
        'Buccaneers' => '/r/buccaneers',
        'Cowboys' => '/r/cowboys',
        'Giants' => '/r/nygiants',
        'Eagles' => '/r/eagles',
        'Redskins' => '/r/washingtonNFL',
        'Cardinals' => '/r/azcardinals',
        'Rams' => '/r/LosAngelesRams',
        '49ers' => '/r/49ers',
        'Seahawks' => '/r/seahawks',    
    ];
    if (strpos($team, 'Washington') !== FALSE) {
        return $subreddits['Redskins'];
    }
    else {
        $name = end(explode(' ', $team));
        return $subreddits[$name];
    } 
}

?>