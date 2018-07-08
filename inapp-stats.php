<?php

require 'includes/master.inc.php';
$Auth->requireAdmin('login.php');
$nav = 'inapp-stats';

################# google charts
function makeGoogleChart($result, $div_id, $title, $width = null, $height = null) {
	$std_colors = array('#FF0000', '#0DFF00', '#0008FF', '#FF8000', 'AntiqueWhite', 'Aqua', 'BlueViolet', 'Brown', 
			'Chocolate', 'CornflowerBlue', 'Crimson', 'DarkSalmon', 'DimGray', 'GoldenRod', 'PowderBlue');
	
	?>
	<script type="text/javascript">
		google.load("visualization", "1", {packages:["corechart"]});
		google.setOnLoadCallback(function () {
			var data = new google.visualization.DataTable();
			<?php
			$colors = array();
			foreach ($result['columns'] as $index => $col_data) {
				if ($index > 0) $colors[] = "'".(isset($col_data['color']) ? $col_data['color'] : array_shift($std_colors))."'";
				echo "data.addColumn('".$col_data['type']."', '".$col_data['name']."');".PHP_EOL;
			}
			?>
			data.addRows(<?php echo count($result['rows']); ?>);
			<?php
			foreach ($result['rows'] as $row_index => $row_data) {
				foreach ($row_data as $index => $data) {
					echo "data.setValue($row_index, $index, ".($result['columns'][$index]['type'] == 'string' ? "'$data'" : $data).");".PHP_EOL;
				}
			}
			?>
			var chart = new google.visualization.LineChart(document.getElementById('<?php echo $div_id; ?>'));
			chart.draw(data, {<?php echo (!empty($width) ? 'width: '.$width.', ' : '').(!empty($height) ? 'height: '.$height.', ' : ''); ?>title: '<?php echo $title; ?>', colors:[<?php echo implode(',', $colors)?>], gridlineColor: '#CCC', pointSize: '3'});
		});
	</script>
	<div id="<?php echo $div_id; ?>"></div>
	<?php
}


$url = array(
	'id' => getParams($_GET, 'id', 'int', 0),
	'from_date' => getParams($_GET, 'from_date', 'str', '', true)
);

$db = Database::getDatabase();
$applications = DBObject::glob('Application', 'SELECT * FROM shine_applications ORDER BY name');
$count = 0;

if (!isset($_GET['tables'])) {
	$from_date = $url['from_date'];
	if (($from_time = strtotime($from_date)) == false) $from_time = mktime(0, 0, 0, date("m")-1, date("d"), date("Y"));
	$from_date = date('Y-m-d 00:00:00', $from_time);
	
	$sql = "SELECT DATE(trx_date) AS date, app_id, COUNT(*) AS count, SUM(price) AS money
		FROM shine_inapp 
		WHERE trx_date >= '".$from_date."'".(!empty($url['id']) ? ' AND app_id = '.$url['id'] : '')."
		GROUP BY app_id, DATE(trx_date)
		ORDER BY trx_date DESC";
	
	# Getting raw statistics data
	$db_res = $db->getRows($sql);
	$db_processed = array();
	foreach ($db_res as $row) {
		$db_processed[$row['date']][$row['app_id']] = array($row['count'], $row['money']);
	}
	
	$stats = array(
		'columns' => array(
			array(
				'name' => 'Date',
				'type' => 'string'
			)
		),
		'rows' => array()
	);
	
	# Add needed applications to stats
	$app_ids = array();
	foreach ($applications as $a) {
		if (empty($url['id']) || $a->id == $url['id']) {
			$app_ids[$a->id] = $a->name;
			$stats['columns'][] = array(
				'name' => $a->name,
				'type' => 'number'
			);
		}
	}
	$stats2 = $stats;
	
	# Process stats
	for ($i = $from_time; $i < time(); $i+=86400) {
		$date_row = date('Y-m-d', $i);
		$stat_row = $stat_row2 = array(date('d M', $i));
		foreach ($app_ids as $a_id => $a_name) {
			$stat_row[] = !empty($db_processed[$date_row][$a_id][0]) ? $db_processed[$date_row][$a_id][0] : '0';
			$stat_row2[] = !empty($db_processed[$date_row][$a_id][1]) ? $db_processed[$date_row][$a_id][1] : '0.00';
		}
		$stats['rows'][] = $stat_row;
		$stats2['rows'][] = $stat_row2;
	}
	
	$db->query("SELECT COUNT(*) AS count FROM shine_inapp".(!empty($url['id']) ? " WHERE app_id = ".$url['id'] : ""));
	if ($db->hasRows()) {
		$row = $db->getRow();
		$count = $row['count'];
	}
}
else {
	$url['tables'] = true;
	$per_page = 50;
	$page = getParams($_GET, 'page', 'int', 1);
	
	$stats = DBObject::glob('Inapp', "SELECT SQL_CALC_FOUND_ROWS * FROM shine_inapp".(!empty($url['id']) ? " WHERE app_id = ".$url['id'] : "")." ORDER BY trx_date DESC LIMIT ".($per_page*($page-1)).", ".$per_page);
	$count = $db->getValue("SELECT FOUND_ROWS()");
	
	$pager = new Pager($page, $per_page, $count);
}
?>
<?PHP include('inc/header.inc.php'); ?>

<?php if (empty($url['tables'])) {?>
	<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<?php }?>

<div id="bd">
	<div id="yui-main">
		<div class="yui-b" style="margin-right: 1%!important; min-width: 870px!important;"><div class="yui-g">
			<div class="block tabs"><div class="hd">
				<ul>
					<li class="<?PHP if(!isset($url['tables'])) echo 'active'; ?>"><a href="<?php echo $nav.'.php';?>">Graphics</a></li>
					<li class="<?PHP if(isset($url['tables'])) echo 'active'; ?>"><a href="<?php echo $nav.'.php?tables';?>">Tables</a></li>
				</ul>
				<div class="clear"></div>
			</div><div class="bd" style="background: #EEEEF3!important;">
				<div class="block tabs spaces"><div class="hd">
					<ul>
						<li class="<?PHP if(empty($url['id'])) echo 'active'; ?>"><a href="<?PHP echo $nav.'.php?'.http_build_query(array_merge($url, array('id' => 0))); ?>">All Apps</a></li>
						<?PHP foreach($applications as $a) : ?>
							<li class="<?PHP if($url['id'] == $a->id) echo 'active'; ?>"><a href="<?PHP echo $nav.'.php?'.http_build_query(array_merge($url, array('id' => $a->id))); ?>"><?PHP echo $a->name; ?></a></li>
						<?PHP endforeach; ?>
					</ul>
					<div class="clear"></div>
				</div>
				<div class="bd">
					<div class="total_num">Total purchases: <?php echo $count; ?></div>
					
					<?php 
					######################################################## TABLE VIEW ########################################################
					if (isset($url['tables'])) {?>
						<ul class="pager">
							<li><a href="<?PHP echo $nav.'.php?'.http_build_query(array_merge($url, array('page' => $pager->prevPage()))); ?>">&#171; Prev</a></li>
							<?PHP for($i = 1; $i <= $pager->numPages; $i++) : ?>
								<?PHP if($i == $pager->page) : ?><li class="active"><?PHP else : ?><li><?PHP endif; ?>
								<a href="<?PHP echo $nav.'.php?'.http_build_query(array_merge($url, array('page' => $i))); ?>"><?PHP echo $i; ?></a></li>
							<?PHP endfor; ?>
							<li><a href="<?PHP echo $nav.'.php?'.http_build_query(array_merge($url, array('page' => $pager->nextPage()))); ?>">Next &#187;</a></li>
						</ul>
						<div class="clear"></div>
						
						<table class="lines">
							<thead>
								<tr>
									<td>Application</td>
									<td>Transaction ID</td>
									<td>Item ID</td>
									<td>Bundle Version</td>
									<td>Price</td>
									<td>UUID</td>
									<td>IP</td>
									<td>Country</td>
									<td>Date</td>
									<td>&nbsp;</td>
								</tr>
							</thead>
							<tbody>
							<?PHP foreach($stats as $s) : ?>
								<tr>
									<td><?PHP echo $s->applicationName(); ?></td>
									<td><?PHP echo $s->id; ?></td>
									<td><?PHP echo $s->inapp_id; ?></td>
									<td><?PHP echo $s->bundle_version; ?></td>
									<td><?PHP echo $s->price.' '.$s->currency; ?></td>
									<td><?PHP echo $s->uuid; ?></td>
									<td><?PHP echo $s->ip; ?></td>
									<td><?PHP echo !empty($s->country) ? $s->country : '-'; ?></td>
									<td><?PHP echo date('m/d/Y h:ia', strtotime($s->trx_date)); ?></td>
									<td><a href=""></a></td>
								</tr>
							<?PHP endforeach; ?>
							</tbody>
						</table>
						
						<ul class="pager">
							<li><a href="<?PHP echo $nav.'.php?'.http_build_query(array_merge($url, array('page' => $pager->prevPage()))); ?>">&#171; Prev</a></li>
							<?PHP for($i = 1; $i <= $pager->numPages; $i++) : ?>
								<?PHP if($i == $pager->page) : ?><li class="active"><?PHP else : ?><li><?PHP endif; ?>
								<a href="<?PHP echo $nav.'.php?'.http_build_query(array_merge($url, array('page' => $i))); ?>"><?PHP echo $i; ?></a></li>
							<?PHP endfor; ?>
							<li><a href="<?PHP echo $nav.'.php?'.http_build_query(array_merge($url, array('page' => $pager->nextPage()))); ?>">Next &#187;</a></li>
						</ul>
					<?php }
					######################################################## GRAPHIC VIEW ########################################################
					else {
						makeGoogleChart($stats, 'buy_cnt', 'Buys/Month');
						makeGoogleChart($stats2, 'buy_prc', 'Money/Month');
					}?>
				</div>
				</div>
				&nbsp;
			</div></div>
		</div></div>
	</div>
	<div id="sidebar" class="yui-b"></div>
</div>

<?PHP include('inc/footer.inc.php'); ?>