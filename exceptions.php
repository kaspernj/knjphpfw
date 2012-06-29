<?php

class NotFoundExc extends exception
{
}

class DBConnExc extends exception
{
}

class NoAccessExc extends exception
{
}

function thrownew($msg, $exc = "Exception")
{
    throw new $exc($msg);
}

function knj_upload_error_msgs()
{
    return array(
        UPLOAD_ERR_OK => _("No errors."),
        UPLOAD_ERR_INI_SIZE => _("Larger than upload_max_filesize."),
        UPLOAD_ERR_FORM_SIZE => _("Larger than form MAX_FILE_SIZE."),
        UPLOAD_ERR_PARTIAL => _("Partial upload."),
        UPLOAD_ERR_NO_FILE => _("No file."),
        UPLOAD_ERR_NO_TMP_DIR => _("No temporary directory."),
        UPLOAD_ERR_CANT_WRITE => _("Can't write to disk."),
        UPLOAD_ERR_EXTENSION => _("File upload stopped by extension."),
        UPLOAD_ERR_EMPTY => _("File is empty.")
    );
}

function knj_upload_error_msg($error_code)
{
    $msgs = knj_upload_error_msgs();
    $msg = $msgs[$error_code];
    if (!$msg) {
        throw new exception(sprintf(_("No error-message by that code: '%s'."), $error_code));
    }

    return $msg;
}
