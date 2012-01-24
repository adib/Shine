<?PHP

define('LOCAL_UPLOAD_PATH', dirname(DOC_ROOT).'/shine_uploads');

class LocalUpload {
	public static function uploadFile($fs_path, $name) {
		if (!is_dir(LOCAL_UPLOAD_PATH)) mkdir(LOCAL_UPLOAD_PATH, 0777, true);
		return move_uploaded_file($fs_path, LOCAL_UPLOAD_PATH.'/'.$name);
	}
	
	public static function deleteFile($name) {
		return unlink(LOCAL_UPLOAD_PATH.'/'.$name);
	}
}