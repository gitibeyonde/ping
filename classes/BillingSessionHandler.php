<?php
class BillingSessionHandler implements SessionHandlerInterface
{

    function open($savePath, $sessionName)
    {
        error_log("Session open savepath ". $savePath. " session_name ". $sessionName);
        return true;
    }

    function close()
    {
        error_log("Session close ");
        return true;
    }

    function read($id)
    {
        error_log("Session read ". $id);
        return true;
    }

    function write($id, $data)
    {
        error_log("Session write ". $id. " data ". $data);
        return true;
    }

    function destroy($id)
    {
        // log billing info
        error_log("Session destroyed ". $id);
        return true;
    }

    function gc($maxlifetime)
    {
        return true;
    }
}
?>