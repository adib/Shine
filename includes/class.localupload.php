<?PHP

class LocalUpload {
	public static function uploadFile($fs_path, $name) {
		if (!is_dir(LOCAL_UPLOAD_PATH)) mkdir(LOCAL_UPLOAD_PATH, 0777, true);
		return move_uploaded_file($fs_path, LOCAL_UPLOAD_PATH.'/'.$name);
	}
	
	public static function deleteFile($name) {
		$resultCode = unlink(LOCAL_UPLOAD_PATH.'/'.$name);
		if (!empty($alternate_fname))
		{
			unlink(LOCAL_UPLOAD_PATH.'/'.$alternate_fname);
		}
		return $resultCode;
	}
}