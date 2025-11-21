<?php

$truhts = array("What is your biggest fear?", "Have you ever lied to your best friend?", "What's your secret talent?");
$dares = array("Do 10 pushups", "make a backflip", "Dance on laps for 5 minutes");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['player_name']) && isset($_POST['choice'])) {
    $playerNmae = htmlspecialchars($_POST['player_name']);
    if ($_POST['choice'] == "truth") {
        $index = rand(0, count($truhts) -1 );
        $question = $truhts[$index];
        echo "Truth for playerName: " . $question;
    } else if ($_POST['choice'] == "dare") {
    $index = rand(0, count($dares) -1);
    $dare = $dares[$index];
    echo "Dare for playerName: " . $dare;
    } else {
    echo "Please select Truth or Dare";
}
}

?>
<form>
    Name: <input type="text" name="player_name"><br>
    <input type="radio" name="choice" value="truth"> Truth
    <input type="radio" name="choice" value="dare"> Dare
    <input type="submit" value="Play">
</form>