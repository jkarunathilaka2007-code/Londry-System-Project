<?php
// Session එක ආරම්භ කරන්න
session_start();

// පද්ධතියේ දැනට පවතින සියලුම Session variables ඉවත් කරන්න
$_SESSION = array();

// Session එකට අදාළ Cookie එකක් තිබේ නම් එයද විනාශ කරන්න
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// අවසානයේ Session එක සම්පූර්ණයෙන්ම විනාශ කරන්න
session_destroy();

// නැවත Login පේජ් එකට යොමු කරන්න
header("Location: index.php");
exit();
?>