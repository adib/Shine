<?php
require 'includes/master.inc.php';
$Auth->requireAdmin('login.php');
$nav = 'applications';

$app = new Application($_GET['id']);
if(!$app->ok()) redirect('index.php');

if (!empty($_REQUEST['act'])) {
	switch ($_REQUEST['act']) {
		case 'delete':
			$lt = new LicenseType($_REQUEST['lt_id']);
			echo ($lt->ok() && $lt->delete()) ? 'OK' : 'Error';
			exit;
		case 'add':
			$lt = new LicenseType();
		case 'edit':
			if (!isset($lt)) $lt = new LicenseType($_REQUEST['lt_id']);
			
			if (!empty($_REQUEST['abbreviation'])) {
				$lt->app_id = $app->id;
				$lt->abbreviation = $_REQUEST['abbreviation'];
				$lt->quantity = (int)$_REQUEST['quantity'];
				$lt->serials_quantity = (int)$_REQUEST['serials_quantity'];
				$lt->expiration_days = (int)$_REQUEST['expiration_days'];
				$lt->max_update_version = $_REQUEST['max_update_version'];
				
				$lt->save();
			}
			else $lt->id = null;
			
			echo $lt->ok() ? 'OK' : 'Error';
			exit;
	}
}

$license_types = $app->license_types();

include('inc/header.inc.php');
?>
<style>
<!--
.hid {display: none;}
.error_lt {color: #aa1010!important;}
-->
</style>
        <div id="bd">
            <div id="yui-main">
                <div class="yui-b"><div class="yui-g">

                    <div class="block tabs spaces">
                        <div class="hd">
                            <h2>Applications</h2>
							<ul>
								<li><a href="application.php?id=<?PHP echo $app->id; ?>"><?PHP echo $app->name; ?></a></li>
								<li class="active"><a href="license-types.php?id=<?PHP echo $app->id; ?>">License types</a></li>
								<li><a href="versions.php?id=<?PHP echo $app->id; ?>">Versions</a></li>
								<li><a href="version-new.php?id=<?PHP echo $app->id; ?>">Release New Version</a></li>
							</ul>
							<div class="clear"></div>
                        </div>
                        <div class="bd">
                        				<h3 class="hid error_lt"></h1>
							<table>
								<thead>
									<tr>
										<th>Abbreviation</th>
										<th>Activations quantity</th>
										<th>Serials quantity</th>
										<th>Expiration days</th>
										<th>Max update version</th>
										<th></th>
									</tr>
								</thead>
								<tbody>
									<?PHP foreach($license_types as $lt) : ?>
									<tr>
										<td>
											<span class="show_lt"><?php echo $lt->abbreviation; ?></span>
											<input type="hidden" name="lt_id" value="<?php echo $lt->id; ?>">
											<input type="hidden" name="id" value="<?php echo $app->id; ?>">
											<input class="show_lt hid" type="text" maxlength="100" name="abbreviation" value="<?php echo $lt->abbreviation; ?>">
										</td>
										<td>
											<span class="show_lt"><?php echo $lt->quantity; ?></span>
											<input class="show_lt hid" type="number" min="0" max="10000" name="quantity" value="<?php echo $lt->quantity; ?>">
										</td>
										<td>
											<span class="show_lt"><?php echo $lt->serials_quantity; ?></span>
											<input class="show_lt hid" type="number" min="0" max="10000" name="serials_quantity" value="<?php echo $lt->serials_quantity; ?>">
										</td>
										<td>
											<span class="show_lt"><?php $val = $lt->expiration_days; echo !empty($val) ? $val : 'Lifetime'; ?></span>
											<input class="show_lt hid" type="number" min="0" name="expiration_days" value="<?php echo $lt->expiration_days; ?>">
										</td>
										<td>
											<span class="show_lt"><?php $val = $lt->max_update_version; echo !empty($val) ? $val : 'All versions'; ?></span>
											<input class="show_lt hid" type="text" maxlength="255" name="max_update_version" value="<?php echo $lt->max_update_version; ?>">
										</td>
										<td>
											<a class="edit_lt show_lt" href="#">Edit</a><br>
											<a class="delete_lt show_lt" href="#">Delete</a>
											
											<a class="save_lt show_lt hid" href="#">Save</a><br>
											<a class="cancel_lt show_lt hid" href="#">Cancel</a>
										</td>
									</tr>
									<?PHP endforeach; ?>
									
									<tr>
										<td><input type="text" maxlength="100" name="abbreviation" id="abbreviation"></td>
										<td><input type="number" min="1" max="10000" name="quantity" id="quantity" value="1"></td>
										<td><input type="number" min="1" max="10000" name="serials_quantity" id="serials_quantity" value="1"></td>
										<td><input type="number" min="0" name="expiration_days" id="expiration_days" value="0"></td>
										<td><input type="text" maxlength="255" name="max_update_version" id="max_update_version"></td>
										<td><a class="add_lt" href="#">Add new</a></td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
              
                </div></div>
            </div>
            <div id="sidebar" class="yui-b">

            </div>
        </div>
<script type="text/javascript">
$(function(){
	$('.add_lt, .save_lt').click(function(){
		var in_data = {};
		if ($(this).hasClass('add_lt')) in_data.act = 'add';
		else in_data.act = 'edit';
		
		$(this).parent().siblings().children('input').each(function(){
			in_data[$(this).attr('name')] = $(this).val();
		});
		$.get(document.location, in_data, function(data){
			if (data && data == 'OK') window.location.reload();
			else {
				var err = 'Could not save to database. Make sure input values are correct.';
				$('.error_lt').removeClass('hid').html(err);
			}
			return false;
		});
		return false;
	});
	$('.edit_lt, .cancel_lt').click(function(){
		$(this).parent().parent().find('.show_lt').toggleClass('hid');
		return false;
	});
	$('.delete_lt').click(function(){
		var top = $(this).parent().siblings();
		var in_data = {'act': 'delete', 'lt_id': top.find('[name=lt_id]').attr('value'), 'id': top.find('[name=id]').attr('value')};
		
		$.get(document.location, in_data, function(data){
			if (data && data == 'OK') window.location.reload();
			else {
				var err = 'Could not delete from database :(';
				$('.error_lt').removeClass('hid').html(err);
			}
			return false;
		});
		return false;
	});
});
</script>
<?PHP include('inc/footer.inc.php'); ?>
