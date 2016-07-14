<?php
date_default_timezone_set('UTC');
?><!DOCTYPE html>
<title>#tppleague IRC logs</title>
<meta charset=utf-8>
<meta name=viewport content="width=device-width">
<link rel=stylesheet href="style.css">
<h1>#tppleague IRC logs</h1>
<p>IRC channel is available on <a href="irc://irc.freenode.net/tppleague">
#tppleague at irc.freenode.net</a>.<br><?php
date_default_timezone_set('UTC');
if (isset($_GET['q'])) {
    echo '<a href="/irc/">Cancel search</a>';
}
else {
    if (isset($_GET['date'])) {
        try {
            $today = new DateTime($_GET['date']);
        }
        catch (Exception $e) {
            die("<p>Cannot parse the date.");
        }
        $formatted = $today->format('Y-m-d\TH:') . '00';
        if ($formatted !== $_GET['date'] || strpos($_SERVER["REQUEST_URI"], 'date') !== FALSE) {
	    header("Location: /irc/$formatted");
        }
    }
    else {
        $today = new DateTime;
    }
    $hour = $today->format('H');
    $today = new DateTime($today->format('Y-m-d') . " $hour:00");
    $tomorrow = clone $today;
    $tomorrow->add(new DateInterval('PT1H'));
    $yesterday = clone $today;
    $interval = new DateInterval('PT1H');
    $interval->invert = true;
    $yesterday->add($interval);

    echo 'Current hour: ', $today->format('Y-m-d H:i'), ' UTC | ';
    echo '<a href="', $yesterday->format('Y-m-d\TH:i'), '#end">Previous hour</a>';
    if (isset($_GET['date']) && $today->format('Y-m-d H:i') < date('Y-m-d H:i')) {
        echo ' | <a href="', $tomorrow->format('Y-m-d\TH:i'), '">Next hour</a>';
    }

}
?></p>
<form action="/irc/" method=get>
<label>Search: <input name=q type=search></label>
<input type=submit value='Search'>
</form>
<form action="/irc/" method=get>
<label>Time: <input name=date type=text placeholder="<?php echo isset($_GET['date']) ? htmlspecialchars($_GET['date']) : date('Y-m-d H:i'); ?>"></label>
<input type=submit value='Go to'>
</form>
<hr>
<?php
$database = pg_connect('dbname=showdown');

function table_header() {
    echo '
        <table>
        <tr>
        <th>
        Time
        <th>
        Sender
        <th>
        Message
    ';
}

function table_end() {
    echo '</table><div id=end></div>';
}

function display_row($row) {
    static $previous_sender;
    static $previous_time;

    list ($id, $time, $sender, $message, $type) = $row;

    $unix_time = strtotime($time);
    $formatted_time = date('H:i', $unix_time);
    $formatted_date = date('Y-m-d+H:00', $unix_time);

    if ($type === '1') {
        if ($previous_sender === $sender) {
            $sender = '';
        }
        else {
            $previous_sender = $sender;
        }
    }
    else {
        $previous_sender = NULL;
    }
    if ($previous_time === $formatted_time) {
        $formatted_time = '';
    }
    else {
        $previous_time = $formatted_time;
    }
    $sender = preg_replace('/!.*/', '', $sender);
    echo '<tr id=id', $id, '><td><a href="?date=', $formatted_date,
        '#id', $id, '">', $formatted_time, '</a>';

    switch ($type) {
    case 1:
        echo '<td>', color_nick($sender), '<td>', parse_message($message);
        break;
    case 2:
        echo '<td>[', color_nick($sender), ']<td>', parse_message($message);
        break;
    case 4:
        echo '<td class=action>-*-<td class=action>', color_nick($sender), ' ', parse_message($message);
        break;
    case 8:
        echo '<td class=meta>&lt;-&gt;<td class=meta>', color_nick($sender), ' is now known as ', color_nick($message);
        break;
    case 16:
        echo '<td class=meta>***<td class=meta>Mode ', htmlspecialchars($message), ' by ', color_nick($sender);
        break;
    case 32:
        echo '<td class=meta>--&gt;<td class=meta>', color_nick($sender), ' has joined';
        break;
    case 64:
        echo '<td class=meta>&lt;--<td class=meta>', color_nick($sender), ' has left';
        break;
    case 128:
        echo '<td class=meta>&lt;--<td class=meta>', color_nick($sender), ' has quit';
        break;
    case 256:
        list ($person, $reason) = explode(' ', $message, 2);
        echo '<td class=meta>&lt;-*<td class=meta>', color_nick($sender), ' has kicked ', color_nick($person), ' (', parse_message($reason), ')';
        break;
    case 16384:
        echo '<td class=meta>*<td class=meta>', parse_message($message);
        break;
    case 32768:
        echo '<td class=meta>&lt;==<td class=meta>Netsplit ended!';
        break;
    case 65536:
        echo '<td class=meta>==&gt;<td class=meta>Netsplit began!';
        break;
    default:
        echo '<td>???<td>', 'Not implemented event type: ', $type;
    }
}

function color_nick($nick) {
    $crc = crc32(preg_replace('/[0-9_]+$/', '', $nick)) % 360;
    return '<span style="color: hsl(' . $crc . ', 100%, 40%)">' . htmlspecialchars($nick) . '</span>';
}

function parse_message($message) {
    $regex = '{\bhttps?://(?:[-A-Za-z0-9+@#/%?=~_()|!:,.;]|&amp;)*(?:[-A-Za-z0-9+@#/%=~_()|]|&amp;)}';

    return preg_replace($regex, '<a href="$0">$0</a>', htmlspecialchars($message));
}

if (isset($_GET['q']) && isset($_GET['date'])) {
    die('<p>Not implemented yet.');
}

if (isset($_GET['q'])) {
    $lines = pg_query_params($database, "
        SELECT messageid, time AT TIME ZONE 'UTC', sender, message, type
        FROM backlog
        JOIN sender USING (senderid)
        WHERE text_search @@ plainto_tsquery($1)
        AND type IN (1, 2, 4)
        AND message !~ '^ '
        ORDER BY time DESC
    ", array(
        $_GET['q']
    ));

    $previous_date = NULL;
    while ($row = pg_fetch_row($lines)) {
        $date = date('Y-m-d', strtotime($row[1]));
        if ($date !== $previous_date) {
            if ($previous_date !== NULL) {
                table_end();
            }
            echo "<h2>$date</h2>";
            table_header();
            $previous_date = $date;
        }
        display_row($row);
    }
    if ($previous_date !== NULL) {
        table_end();
    }
    else {
        echo '<p>No records found.';
    }
}
else {
    $lines = pg_query_params($database, "
        SELECT messageid, time AT TIME ZONE 'UTC', sender, message, type
        FROM backlog
        JOIN sender USING (senderid)
        WHERE time >= $1
        AND time < $1 + interval '1 hour'
        AND message !~ '^ '
        ORDER BY time
    ", array($today->format('Y-m-d H:i:s') . 'Z'));

    $first = true;
    while ($row = pg_fetch_row($lines)) {
        if ($first) {
            table_header();
            $first = false;
        }
        display_row($row);
    }
    table_end();
    if ($first) {
        die('<p>Date doesn\'t have data logged.');
    }
}
?>
