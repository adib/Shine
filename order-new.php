<?PHP
	require 'includes/master.inc.php';
	$Auth->requireAdmin('login.php');
	$nav = 'orders';
	
	if(isset($_POST['btnCreateOrder']))
	{
		$Error->blank($_POST['app_id'], 'Application');
		$Error->blank($_POST['first_name'], 'First Name');
		$Error->blank($_POST['last_name'], 'Last Name');
		$Error->email($_POST['email']);
		
		if (!empty($_POST['license_type'])) {
			$lt = new LicenseType($_POST['license_type']);
			if ($lt->ok()) {
				$_POST['quantity'] = $lt->quantity;
				$_POST['expiration_date'] = $lt->expiration_days > 0 ? date('Y-m-d', time() + $lt->expiration_days*86400) : '0000-00-00';
			}
			else $Error->add('License Type', 'You must enter a valid license type');
		}
		else $Error->range($_POST['quantity'], 1, 1000, 'Quantity');
		
		if($Error->ok())
		{
		    $app = new Application($_POST['app_id']);
		    
			$o = new Order();
			$o->first_name  = $_POST['first_name'];
			$o->last_name   = $_POST['last_name'];
			$o->payer_email = $_POST['email'];
			$o->app_id      = $_POST['app_id'];
			$o->notes       = $_POST['notes'];
			$o->quantity = $_POST['quantity'] * $lt->serials_quantity;
			$o->expiration_date = $_POST['expiration_date'];
			$o->type        = 'Manual';
			$o->dt          = dater();
			$o->item_name   = $app->name;
			if (isset($lt)) $o->license_type_id = $lt->id;
			if ($app->activation_online == '1') {
				$serials = array();
				
				for ($i = 0; $i < $lt->serials_quantity; $i++) {
					$o->generateSerial(); # generates serial into $o->serial_number	
					$serials[] = $o->serial_number;
				}
				
				$o->load(array('serial_number' => implode(',', $serials)));
				$id = $o->save();
			}

			if ($app->activation_online != '1') $o->generateLicense();

			redirect('order.php?id=' . $o->id);
		}
		else
		{
			$first_name = $_POST['first_name'];
			$last_name  = $_POST['last_name'];
			$email      = $_POST['email'];
			$notes      = $_POST['notes'];
			$quantity   = $_POST['quantity'];
			$expiration_date = $_POST['expiration_date'];
		}
	}
	else
	{
		$first_name = '';
		$last_name  = '';
		$email      = '';
		$notes      = '';
		$quantity   = '1';
		$expiration_date = date('Y-m-d');
	}
	
	$applications = DBObject::glob('Application', 'SELECT * FROM shine_applications ORDER BY name');
	$license_types_raw = DBObject::glob('LicenseType', 'SELECT * FROM shine_license_types');
	$license_types = array();
	foreach ($license_types_raw as $lt) {
		$license_types[$lt->app_id][$lt->id] = $lt->abbreviation;
	}
	
	foreach ($applications as $a) {
		if ($a->activation_online != '1') unset($license_types[$a->id]);
	}
	
?>
<?PHP include('inc/header.inc.php'); ?>
<script type="text/javascript">
$(function(){
	var license_types = <?php echo json_encode($license_types); ?>;

	$('#app_id').change(function(){
		var app_id = $(this).val();
		if (license_types[app_id]) {
			if (!$('.lt_manual').hasClass('hid')) $('.lt_manual').addClass('hid');
			$('.lt_select').removeClass('hid');

			$('#license_type').empty();
			$.each(license_types[app_id], function(i, v){
				$('#license_type').append('<option value="'+i+'">'+v+'</option>');
			});
		}
		else {
			if (!$('.lt_select').hasClass('hid')) $('.lt_select').addClass('hid');
			$('.lt_manual').removeClass('hid');
		}
	});

	$('#app_id').trigger('change');
});
</script>
        <div id="bd">
            <div id="yui-main">
                <div class="yui-b"><div class="yui-g">
					<?PHP echo $Error; ?>
                    <div class="block">
                        <div class="hd">
                            <h2>Create Manual Order</h2>
                        </div>
                        <div class="bd">
							<form action="order-new.php" method="post">
								<p><label for="app_id">Application</label> <select name="app_id" id="app_id"><?PHP foreach($applications as $a) : ?><option value="<?PHP echo $a->id; ?>"><?PHP echo $a->name; ?></option><?PHP endforeach; ?></select></p>
								<p><label for="first_name">First Name</label> <input type="text" name="first_name" id="first_name" value="<?PHP echo $first_name; ?>" class="text"></p>
								<p><label for="last_name">Last Name</label> <input type="text" name="last_name" id="last_name" value="<?PHP echo $last_name; ?>" class="text"></p>
								<p><label for="email">Email</label> <input type="text" name="email" id="email" value="<?PHP echo $email; ?>" class="text"></p>
								<div class="lt_select hid">
									<p>
										<label for="license_type">License type</label>
										<select name="license_type" id="license_type">
											
										</select>
									</p>
								</div>
								<div class="lt_manual">
									<p><label for="quantity">Quantity</label> <input type="text" name="quantity" id="quantity" value="<?PHP echo $quantity; ?>" class="text"></p>
									<p><label for="expiration_date">Expiration date</label> <input type="date" name="expiration_date" id="expiration_date" value="<?PHP echo $expiration_date; ?>" class="text"></p>
								</div>
								<p><p><label for="notes">Notes</label> <textarea name="notes" id="notes" class="text"><?PHP echo $notes; ?></textarea></p>
								<p><input type="submit" name="btnCreateOrder" value="Create Order" id="btnCreateOrder"></p>
							</form>
						</div>
					</div>
              
                </div></div>
            </div>
            <div id="sidebar" class="yui-b">

            </div>
        </div>

<?PHP include('inc/footer.inc.php'); ?>
