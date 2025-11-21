
<?php
// Truth or Dare Game without errors

$truths = array("What is your biggest fear?", "Have you ever lied to your best friend?", "What's your secret talent?");
$dares = array("Do 10 pushups", "make a backflip", "Dance on laps for 5 minutes");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['player_name']) && isset($_POST['choice'])) {
    $playerName = htmlspecialchars($_POST['player_name']);
    if ($_POST['choice'] == "truth") {
        $index = rand(0, count($truths) - 1);
        $question = $truths[$index];
        echo "Truth for $playerName: " . $question;
    } else if ($_POST['choice'] == "dare") {
        $index = rand(0, count($dares) - 1);
        $dare = $dares[$index];
        echo "Dare for $playerName: " . $dare;
    } else {
        echo "Please select Truth or Dare";
    }
}

?>
<form method="post" action="">
    Name: <input type="text" name="player_name" required><br>
    <input type="radio" name="choice" value="truth" required> Truth
    <input type="radio" name="choice" value="dare" required> Dare
    <input type="submit" value="Play">
</form>