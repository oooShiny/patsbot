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

    $weather = $game['weather']['highTemperature'] . '°, ' . $game['weather']['displayValue'];
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
        asort($hls);
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

?>

 <!-- Post Title -->
 <h2>
    <?php print $title; ?> 
    <?php print $away_team; ?> (<?php print $game['away']['record']; ?>)  
    @ 
    <?php print $home_team; ?> (<?php print $game['home']['record']; ?>)
    <?php if ($game_status != 'Final'): ?>
        [kickoff <?php print $game['time']; ?>]
    <?php endif; ?>
</h2>

    <!-- Season & Week -->
    # <?php print $season_week; ?>
    
    <br>
    ---
    <br>

    <!-- Teams -->
    # [<?php print $away_team; ?>](<?php print get_subreddit_link($away_team); ?>#away) (<?php print $game['away']['record']; ?>)  
    at 
    [<?php print $home_team; ?>](<?php print get_subreddit_link($home_team); ?>#home) (<?php print $game['home']['record']; ?>)

    <br>

    <!-- Stadium & Location -->
    <?php print $game['venue']['fullName']; ?> in <?php print $game['venue']['address']['city']; ?>, <?php print $game['venue']['address']['state']; ?>
    
    <br><br>

    <?php if ($game_status == 'Final'): ?>
        <!-- Game Score -->
        ## Box Score 
        <br><br>
         &nbsp; | 1 | 2 | 3 | 4 | Final <br>
        ---|---|---|---|---|--- <br>
        <?php print $away_team; ?> | <?php foreach ($game['away']['box'] as $q) { print $q['value'] . ' | '; } print $game['away']['score']; ?> 
        <br>
        <?php print $home_team; ?> | <?php foreach ($game['home']['box'] as $q) { print $q['value'] . ' | '; } print $game['home']['score']; ?> 
        
        <br><br>

    <?php else: ?>
        <!-- Game Date  -->
        ## <?php print $game_status; ?>
        <br><br>
    <?php endif; ?>

    <!-- Odds -->
    <?php if ($game_status !== 'Final'): ?>
        * **Favorite:** <?php print $game['favorite']; ?>
        <br>
        * **Over/Under:** <?php print $game['ou']; ?>
        <br>
        * **Weather:** <?php print $game['weather']; ?>
        <br><br>
    <?php endif; ?>

    <!-- Highlights -->
    <?php if (!empty($highlights)) {
        print '## Highlights <br> *Courtesy of u/timnog* <br><br>';
        foreach ($highlights as $h) {
            print '1. ' . $h . '<br>';
        }
        print '<br><br>';
    } ?>


    Game Thread Notes |<br>
    :--- |<br>
    Discuss whatever you wish. You can trash talk, but keep it civil. |<br>
    If you are experiencing problems with comment sorting in the official reddit app, we suggest using a third-party client instead ([Android](/r/Android/comments/f8tg1x/which_reddit_app_do_you_use_and_why_2020_edition/), [iOS](/r/applehelp/comments/a6pzha/best_reddit_app_for_ios/)). |<br>
    Turning comment sort to 'new' will help you see the newest comments. |<br>
    Try the [Tab Auto Refresh](https://mybrowseraddon.com/tab-auto-refresh.html) browser extension to auto-refresh this tab. |<br>
    Use [reddit-stream.com](https://reddit-stream.com/) to get an autorefreshing version of this page. |<br>
    
    <pre><?php //var_dump($hls); ?></pre>

<?php } // endforeach; ?>



<?php

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