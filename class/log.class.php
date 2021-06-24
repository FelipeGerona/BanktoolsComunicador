<?php 
class LOG{
    
    private static $DirLog = 'log'.DIRECTORY_SEPARATOR;
    
    public static function setLOG($type, $message, $file, $method, $line){
        $FileLog = self::$DirLog.date("Ymd")."_$type.log";
        
        $txt = "dhOperacao : ".date("Y-m-d H:i:s").PHP_EOL;
        $txt.= "Method ....: $method".PHP_EOL;
        $txt.= "File ......: $file".PHP_EOL;
        $txt.= "Line ......: $line".PHP_EOL;
        $txt.= "Message ...: $message".PHP_EOL.PHP_EOL;
        file_put_contents($FileLog, $txt, FILE_APPEND);
        /*
        $data = array(
            'dhOperacao' => date("Y-m-d H:i:s"),
            'tipo' => $type,
            'method' => $method,
            'file' => $file,
            'line' => $line,
            'message' => $message
        );
        */
        
    }
}

?>