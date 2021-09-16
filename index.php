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
    ];
    
    if ($game['status']['type']['detail'] == 'Final') {
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
        // asort($hls);
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
    if ($game_status == 'Final') {
        $title = 'Official Post-Game Thread: ';
    }
    else {
        $title = 'Official Game Day Thread: ';
    }

    // Build Post.
    $post_title = '';
    $post = '';
    // Post Title

    $post_title .= $title;
    $post_title .= $away_team . ' (' . $game['away']['record'] . ')';  
    $post_title .= ' @ '; 
    $post_title .= $home_team . ' (' . $game['home']['record'] . ')'; 
     if ($game_status != 'Final') {
        $post_title .= ' [kickoff ' . $game['time'] . ']';
    }

    // Season & Week
    $post .= '#' . $season_week . "\n --- \n";

    // Teams
    $post .= '# [' . $away_team . '](' . get_subreddit_link($away_team) . '#away) (' . $game['away']['record'] . ')';  
    $post .= ' at ';
    $post .= '[' . $home_team . '](' . get_subreddit_link($home_team) . '#home) (' . $game['home']['record'] . ')';  
    $post .= "\n";

    // Stadium & Location
    $post .= $game['venue']['fullName'] . ' in ' . $game['venue']['address']['city'] . ',' . $game['venue']['address']['state'];
    $post .= "\n\n";

    if ($game_status == 'Final') {
        // Game Score
        $post .= '## Box Score';
        $post .= "\n\n";
        $post .= ' | 1 | 2 | 3 | 4 | Final' . "\n";
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
    if ($game_status !== 'Final') { 
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


    $post .= 'Game Thread Notes |' . "\n";
    $post .= ':--- |' . "\n";
    $post .= 'Discuss whatever you wish. You can trash talk, but keep it civil. |' . "\n";
    $post .= 'If you are experiencing problems with comment sorting in the official reddit app, we suggest using a third-party client instead ([Android](/r/Android/comments/f8tg1x/which_reddit_app_do_you_use_and_why_2020_edition/), [iOS](/r/applehelp/comments/a6pzha/best_reddit_app_for_ios/)). |' . "\n";
    $post .= 'Turning comment sort to \'new\' will help you see the newest comments. |' . "\n";
    $post .= 'Try the [Tab Auto Refresh](https://mybrowseraddon.com/tab-auto-refresh.html) browser extension to auto-refresh this tab. |' . "\n";
    $post .= 'Use [reddit-stream.com](https://reddit-stream.com/) to get an autorefreshing version of this page. |' . "\n";
    
    

} // endforeach;

// Display the post on the screen.
print '<h2>' . $post_title . '</h2>';

print nl2br($post);
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
 * Post the message to Reddit.
 */
function post_to_reddit($post_title, $post) {
    $reddit_info = get_params();
    $auth = reddit_auth($reddit_info);

    $post_data = [
        'title' => $post_title,
        'sr' => 'nflgifbot',
        'kind' => 'self',
        'text' => $post
    ];

    $ch = curl_init('https://oauth.reddit.com/api/submit');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'PatsBot by /u/' . $username);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: " . $auth['token_type'] . " " . $auth['access_token']]);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

    // curl response from our post call
    $response_raw = curl_exec($ch);
    $response = json_decode($response_raw, TRUE);
    curl_close($ch);

    foreach ($response['jquery'] as $key => $value) {
        foreach ($value as $v) {
            if (is_array($v)) {
                foreach ($v as $item)  {
                    if (strpos($item, 'reddit.com') !== FALSE) {
                        $url = parse_url($item);
                        $path = explode('/', $url['path']);
                        var_dump($url);
                        print 'Post ID: ' . $path[3];
                    }
                }
            }
        }
    }
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
    $file = file_get_contents('.env');
    $array = explode("\n", $file);
    $params = [];
    foreach ($array as $param) {
        $p = explode('=', $param);
        $params[$p[0]] = $p[1];
    }
    return $params;
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