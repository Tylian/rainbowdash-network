<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Abstraction for media files in general
 *
 * TODO: combine with ImageFile?
 *
 * PHP version 5
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Media
 * @package   StatusNet
 * @author    Robin Millette <robin@millette.info>
 * @author    Zach Copley <zach@status.net>
 * @copyright 2008-2009 StatusNet, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://status.net/
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

class MediaFile
{

    var $filename      = null;
    var $fileRecord    = null;
    var $user          = null;
    var $fileurl       = null;
    var $short_fileurl = null;
    var $mimetype      = null;

    function __construct($user = null, $filename = null, $mimetype = null)
    {
        if ($user == null) {
            $this->user = common_current_user();
        } else {
            $this->user = $user;
        }

        $this->filename   = $filename;
        $this->mimetype   = $mimetype;
        $this->fileRecord = $this->storeFile();
        $this->thumbnailRecord = $this->storeThumbnail();

        $this->fileurl = common_local_url('attachment',
                                    array('attachment' => $this->fileRecord->id));

        $this->maybeAddRedir($this->fileRecord->id, $this->fileurl);
        $this->short_fileurl = common_shorten_url($this->fileurl);
        $this->maybeAddRedir($this->fileRecord->id, $this->short_fileurl);
    }

    function attachToNotice($notice)
    {
        File_to_post::processNew($this->fileRecord->id, $notice->id);
        $this->maybeAddRedir($this->fileRecord->id,
                             common_local_url('file', array('notice' => $notice->id)));
    }

    function shortUrl()
    {
        return $this->short_fileurl;
    }

    function delete()
    {
        $filepath = File::path($this->filename);
        @unlink($filepath);
    }

    function storeFile() {

        $file = new File;

        $file->filename = $this->filename;
        $file->url      = File::url($this->filename);
        $filepath       = File::path($this->filename);
        $file->size     = filesize($filepath);
        $file->date     = time();
        $file->mimetype = $this->mimetype;

        $file_id = $file->insert();

        if (!$file_id) {
            common_log_db_error($file, "INSERT", __FILE__);
            // TRANS: Client exception thrown when a database error was thrown during a file upload operation.
            throw new ClientException(_('There was a database error while saving your file. Please try again.'));
        }

        return $file;
    }

    /**
     * Generate and store a thumbnail image for the uploaded file, if applicable.
     *
     * @return File_thumbnail or null
     */
    function storeThumbnail()
    {
        if (substr($this->mimetype, 0, strlen('image/')) != 'image/') {
            // @fixme video thumbs would be nice!
            return null;
        }
        try {
            $image = new ImageFile($this->fileRecord->id,
                                   File::path($this->filename));
        } catch (Exception $e) {
            // Unsupported image type.
            return null;
        }

        $outname = File::filename($this->user->getProfile(), 'thumb-' . $this->filename, $this->mimetype);
        $outpath = File::path($outname);

        $maxWidth = common_config('attachments', 'thumb_width');
        $maxHeight = common_config('attachments', 'thumb_height');
        list($width, $height) = $this->scaleToFit($image->width, $image->height, $maxWidth, $maxHeight);

        $image->resizeTo($outpath, $width, $height);
        File_thumbnail::saveThumbnail($this->fileRecord->id,
                                      File::url($outname),
                                      $width,
                                      $height);
    }

    function scaleToFit($width, $height, $maxWidth, $maxHeight)
    {
        $aspect = $maxWidth / $maxHeight;
        $w1 = $maxWidth;
        $h1 = intval($height * $maxWidth / $width);
        if ($h1 > $maxHeight) {
            $w2 = intval($width * $maxHeight / $height);
            $h2 = $maxHeight;
            return array($w2, $h2);
        }
        return array($w1, $h1);
    }

    function rememberFile($file, $short)
    {
        $this->maybeAddRedir($file->id, $short);
    }

    function maybeAddRedir($file_id, $url)
    {
        $file_redir = File_redirection::staticGet('url', $url);

        if (empty($file_redir)) {

            $file_redir = new File_redirection;
            $file_redir->url = $url;
            $file_redir->file_id = $file_id;

            $result = $file_redir->insert();

            if (!$result) {
                common_log_db_error($file_redir, "INSERT", __FILE__);
                // TRANS: Client exception thrown when a database error was thrown during a file upload operation.
                throw new ClientException(_('There was a database error while saving your file. Please try again.'));
            }
        }
    }

    static function fromURI($param = 'media', $uri = null) {
        if(empty($uri)) {
            return;
        }

        $file = file_get_contents(str_replace('data:', 'data://', $uri));
        $tempfile = tempnam(sys_get_temp_dir(), rawurlencode(common_config('site', 'name') . "$param-"));

        $_FILES[$param] = array(
            'tmp_name' => $tempfile,
            'name' => 'tmpfile',
            'error' => UPLOAD_ERR_OK,
            'size' => strlen($file),
        );

        if(strlen($file) > ImageFile::maxFileSizeInt()) {
            $_FILES[$param]['error'] = UPLOAD_ERR_INI_SIZE;
        }
        else {
            if(file_put_contents($tempfile, $file) === false) {
                $_FILES[$param]['error'] = UPLOAD_ERR_CANT_WRITE;
            }
        }

        return MediaFile::fromUpload($param);
    }

    static function fromUpload($param = 'media', $user = null)
    {
        if (empty($user)) {
            $user = common_current_user();
        }

        if (!isset($_FILES[$param]['error'])){
            return;
        }

        switch ($_FILES[$param]['error']) {
        case UPLOAD_ERR_OK: // success, jump out
            break;
        case UPLOAD_ERR_INI_SIZE:
            // TRANS: Client exception thrown when an uploaded file is larger than set in php.ini.
            throw new ClientException(_('The uploaded file exceeds the ' .
                'upload_max_filesize directive in php.ini.'));
            return;
        case UPLOAD_ERR_FORM_SIZE:
            throw new ClientException(
                // TRANS: Client exception.
                _('The uploaded file exceeds the MAX_FILE_SIZE directive' .
                ' that was specified in the HTML form.'));
            return;
        case UPLOAD_ERR_PARTIAL:
            @unlink($_FILES[$param]['tmp_name']);
            // TRANS: Client exception.
            throw new ClientException(_('The uploaded file was only' .
                ' partially uploaded.'));
            return;
        case UPLOAD_ERR_NO_FILE:
            // No file; probably just a non-AJAX submission.
            return;
        case UPLOAD_ERR_NO_TMP_DIR:
            // TRANS: Client exception thrown when a temporary folder is not present to store a file upload.
            throw new ClientException(_('Missing a temporary folder.'));
            return;
        case UPLOAD_ERR_CANT_WRITE:
            // TRANS: Client exception thrown when writing to disk is not possible during a file upload operation.
            throw new ClientException(_('Failed to write file to disk.'));
            return;
        case UPLOAD_ERR_EXTENSION:
            // TRANS: Client exception thrown when a file upload operation has been stopped by an extension.
            throw new ClientException(_('File upload stopped by extension.'));
            return;
        default:
            common_log(LOG_ERR, __METHOD__ . ": Unknown upload error " .
                $_FILES[$param]['error']);
            // TRANS: Client exception thrown when a file upload operation has failed with an unknown reason.
            throw new ClientException(_('System error uploading file.'));
            return;
        }

        if (!MediaFile::respectsQuota($user, $_FILES[$param]['size'])) {

            // Should never actually get here

            @unlink($_FILES[$param]['tmp_name']);
            // TRANS: Client exception thrown when a file upload operation would cause a user to exceed a set quota.
            throw new ClientException(_('File exceeds user\'s quota.'));
            return;
        }

        $mimetype = MediaFile::getUploadedFileType($_FILES[$param]['tmp_name'],
                                                   $_FILES[$param]['name']);

        $filename = null;

        if (isset($mimetype)) {

            $basename = basename($_FILES[$param]['name']);
            $filename = File::filename($user->getProfile(), $basename, $mimetype);
            $filepath = File::path($filename);

            $result = false;
            if(!file_exists($filepath)) {
                $result = move_uploaded_file($_FILES[$param]['tmp_name'], $filepath);
                if(!$result) {
                    $result = rename($_FILES[$param]['tmp_name'], $filepath);
                }
            }

            if (!$result) {
                // TRANS: Client exception thrown when a file upload operation fails because the file could
                // TRANS: not be moved from the temporary folder to the permanent file location.
                throw new ClientException(_('File could not be moved to destination directory.'));
                return;
            }

        } else {
            // TRANS: Client exception thrown when a file upload operation has been stopped because the MIME
            // TRANS: type of the uploaded file could not be determined.
            throw new ClientException(_('Could not determine file\'s MIME type.'));
            return;
        }

        return new MediaFile($user, $filename, $mimetype);
    }

    static function fromFilehandle($fh, $user) {

        $stream = stream_get_meta_data($fh);

        if (!MediaFile::respectsQuota($user, filesize($stream['uri']))) {

            // Should never actually get here

            // TRANS: Client exception thrown when a file upload operation would cause a user to exceed a set quota.
            throw new ClientException(_('File exceeds user\'s quota.'));
            return;
        }

        $mimetype = MediaFile::getUploadedFileType($fh);

        $filename = null;

        if (isset($mimetype)) {

            $filename = File::filename($user->getProfile(), "email", $mimetype);

            $filepath = File::path($filename);

            $result = copy($stream['uri'], $filepath) && chmod($filepath, 0664);

            if (!$result) {
                // TRANS: Client exception thrown when a file upload operation fails because the file could
                // TRANS: not be moved from the temporary folder to the permanent file location.
                throw new ClientException(_('File could not be moved to destination directory.' .
                    $stream['uri'] . ' ' . $filepath));
            }
        } else {
            // TRANS: Client exception thrown when a file upload operation has been stopped because the MIME
            // TRANS: type of the uploaded file could not be determined.
            throw new ClientException(_('Could not determine file\'s MIME type.'));
            return;
        }

        return new MediaFile($user, $filename, $mimetype);
    }

    /**
     * Attempt to identify the content type of a given file.
     * 
     * @param mixed $f file handle resource, or filesystem path as string
     * @param string $originalFilename (optional) for extension-based detection
     * @return string
     * 
     * @fixme is this an internal or public method? It's called from GetFileAction
     * @fixme this seems to tie a front-end error message in, kinda confusing
     * @fixme this looks like it could return a PEAR_Error in some cases, if
     *        type can't be identified and $config['attachments']['supported'] is true
     * 
     * @throws ClientException if type is known, but not supported for local uploads
     */
    static function getUploadedFileType($f, $originalFilename=false) {
        require_once 'MIME/Type.php';
        require_once 'MIME/Type/Extension.php';

        // We have to disable auto handling of PEAR errors
        PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $mte = new MIME_Type_Extension();

        $cmd = &PEAR::getStaticProperty('MIME_Type', 'fileCmd');
        $cmd = common_config('attachments', 'filecommand');

        $filetype = null;

        // If we couldn't get a clear type from the file extension,
        // we'll go ahead and try checking the content. Content checks
        // are unambiguous for most image files, but nearly useless
        // for office document formats.

        if (is_string($f)) {

            // assuming a filename

            $filetype = MIME_Type::autoDetect($f);

        } else {

            // assuming a filehandle

            $stream  = stream_get_meta_data($f);
            $filetype = MIME_Type::autoDetect($stream['uri']);
        }

        // The content-based sources for MIME_Type::autoDetect()
        // are wildly unreliable for office-type documents. If we've
        // gotten an unclear reponse back or just couldn't identify it,
        // we'll try detecting a type from its extension...
        $unclearTypes = array('application/octet-stream',
                              'application/vnd.ms-office',
                              'application/zip',
                              // TODO: for XML we could do better content-based sniffing too
                              'text/xml');

        if ($originalFilename && (!$filetype || in_array($filetype, $unclearTypes))) {
            $type = $mte->getMIMEType($originalFilename);
            if (is_string($type)) {
                $filetype = $type;
            }
        }

        $supported = common_config('attachments', 'supported');
        if (is_array($supported)) {
            // Normalize extensions to mime types
            foreach ($supported as $i => $entry) {
                if (strpos($entry, '/') === false) {
                    common_log(LOG_INFO, "sample.$entry");
                    $supported[$i] = $mte->getMIMEType("sample.$entry");
                }
            }
        }
        if ($supported === true || in_array($filetype, $supported)) {
            // Restore PEAR error handlers for our DB code...
            PEAR::staticPopErrorHandling();
            return $filetype;
        }
        $media = MIME_Type::getMedia($filetype);
        if ('application' !== $media) {
            // TRANS: Client exception thrown trying to upload a forbidden MIME type.
            // TRANS: %1$s is the file type that was denied, %2$s is the application part of
            // TRANS: the MIME type that was denied.
            $hint = sprintf(_('"%1$s" is not a supported file type on this server. ' .
            'Try using another %2$s format.'), $filetype, $media);
        } else {
            // TRANS: Client exception thrown trying to upload a forbidden MIME type.
            // TRANS: %s is the file type that was denied.
            $hint = sprintf(_('"%s" is not a supported file type on this server.'), $filetype);
        }
        // Restore PEAR error handlers for our DB code...
        PEAR::staticPopErrorHandling();
        throw new ClientException($hint);
    }

    static function respectsQuota($user, $filesize)
    {
        $file = new File;
        $result = $file->isRespectsQuota($user, $filesize);
        if ($result === true) {
            return true;
        } else {
            throw new ClientException($result);
        }
    }

}
