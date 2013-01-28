<?PHP
	require 'includes/master.inc.php';
	$Auth->requireAdmin('login.php');
    $db = Database::getDatabase();
	$nav = 'applications';

    // Create a new application if needed
	if(isset($_POST['btnNewApp']) && strlen($_POST['name']))
	{
		$a = new Application();
		$a->name = $_POST['name'];
		$a->insert();
		redirect('application.php?id=' . $a->id);
	}
	
	// Get a list of our apps
	$apps   = DBObject::glob('Application', 'SELECT * FROM shine_applications WHERE hidden = 0 ORDER BY name');
	
	// Get our recent orders
	$orders = DBObject::glob('Order', 'SELECT * FROM shine_orders ORDER BY dt DESC LIMIT 10');

	// Downloads in last 12 hours
	$sel = "DATE_FORMAT(dt, '%Y%m%d%H')";
	$order_totals    = $db->getRows("SELECT $sel as dtstr, COUNT(*) FROM shine_downloads WHERE  DATE_ADD(dt, INTERVAL 12 HOUR) >= NOW() GROUP BY dtstr ORDER BY $sel ASC");
	
	$orderCounts = gimme($order_totals, 'COUNT(*)');
	$orderHours = gimme($order_totals, 'dtstr');
	$orderCombine = (!empty($orderCounts)) ? array_combine($orderHours, $orderCounts) : array();
	
	$order_totals = array();
	for ($i=11; $i>=0; $i--) {
		$nextHour = date('YmdH', strtotime('-'.$i.' hour', time()));
		$key = date('H', strtotime('-'.$i.' hour', time()));
		$order_totals[$key] = (isset($orderCombine[$nextHour])) ? $orderCombine[$nextHour] : 0;
	}
	
	$opw24           = new googleChart(implode(',', $order_totals), 'bary');
	$opw24->showGrid   = 1;
	$opw24->dimensions = '280x100';
	$opw24->setLabelsMinMax(4,'left');
	$opw24->setLabels(implode('|', array_keys($order_totals)), 'bottom');
	$opw24_fb = clone $opw24;
	$opw24_fb->dimensions = '640x400';	

	// Downloads in last 15 days
	$sel = "DATE_FORMAT(dt, '%Y%m%d')";
	$order_totals    = $db->getRows("SELECT $sel as dtstr, COUNT(*) FROM shine_downloads WHERE DATE_ADD(dt, INTERVAL 15 DAY) >= NOW() GROUP BY $sel ORDER BY $sel ASC");

	$orderCounts = gimme($order_totals, 'COUNT(*)');
	$orderDays = gimme($order_totals, 'dtstr');
	$orderCombine = (!empty($orderCounts)) ? array_combine($orderDays, $orderCounts) : array();
	
	$order_totals = array();
	for ($i=14; $i>=0; $i--) {
		$nextDay = date('Ymd', strtotime('-'.$i.' days', time()));
		$key = date('d', strtotime('-'.$i.' days', time()));
		$order_totals[$key] = (isset($orderCombine[$nextDay])) ? $orderCombine[$nextDay] : 0;
	}

	$opw30           = new googleChart(implode(',', $order_totals), 'bary');
	$opw30->showGrid   = 1;
	$opw30->dimensions = '280x100';
	$opw30->setLabelsMinMax(4,'left');
	$opw30->setLabels(implode('|', array_keys($order_totals)), 'bottom');
	$opw30_fb = clone $opw30;
	$opw30_fb->dimensions = '640x400';
	
	
	$chart_dl_24 = new Chart();
	$chart_dl_24->id          = 'chart_dl_24';
	$chart_dl_24->type        = 'line';
	$chart_dl_24->title       = 'App Download Activity by last 24 hours';
	$chart_dl_24->yAxisTitle  = '# Downloads';
	$chart_dl_24->xColumnName = 'dtstr';
	$chart_dl_24->yColumnName = 'COUNT(*)';
	$chart_dl_24->serIdColName   = 'app_id';
	$chart_dl_24->timeInterval = "hour";
	$sel = "DATE_FORMAT(dt, '%Y%m%d%H')";
	$chart_dl_24->query       = "SELECT $sel as dtstr, app_id, name, COUNT(*) FROM shine_downloads sd LEFT JOIN shine_applications sa ON sa.id=sd.app_id WHERE DATE_ADD(dt, INTERVAL 24 HOUR) >= NOW() GROUP BY dtstr, app_id ORDER BY $sel ASC";

	$chart_dl_30 = new Chart();
	$chart_dl_30->id          = 'chart_dl_30';
	$chart_dl_30->type        = 'line';
	$chart_dl_30->title       = 'App Download Activity by last 30 days';
	$chart_dl_30->yAxisTitle  = '# Downloads';
	$chart_dl_30->xColumnName = 'dtstr';
	$chart_dl_30->yColumnName = 'COUNT(*)';
	$chart_dl_30->serIdColName   = 'app_id';
	$chart_dl_30->timeInterval = "day";
	$sel = "DATE_FORMAT(dt, '%Y%m%d')";
	$chart_dl_30->query       = "SELECT $sel as dtstr, app_id, name, COUNT(*) FROM shine_downloads sd LEFT JOIN shine_applications sa ON sa.id=sd.app_id WHERE DATE_ADD(dt, INTERVAL 30 DAY) >= NOW() GROUP BY dtstr, app_id ORDER BY $sel ASC";

	Class Chart
	{
		public $id;
		public $type;
		public $title;
		public $yearColumn;
		public $weekColumn;
		public $xColumnName;
		public $yColumnName;
		public $serIdColName;
		public $query;
		public $yAxisTitle;
		public $series = array();
		public $seriesQuery = "SELECT id, name FROM shine_applications";
		public $timeInterval;

		private $data;

		public function run()
		{
			$db = Database::getDatabase();
			$rows = $db->getRows($this->seriesQuery);
			foreach ($rows as $row) {
				$this->series[$row['id']] = $row['name'];
			}
			
			$rows = $db->getRows($this->query);
			$order_totals = array();
			foreach($rows as $row) {
				$x = $row[$this->xColumnName];
				$y = $row[$this->yColumnName];
				$serId = $row[$this->serIdColName];
				$order_totals[$x][$serId] = $y;
			}

			$this->data = array();
			switch ($this->timeInterval) {
				case 'hour':
					$start = 23;
				break;
				case 'day':
					$start = 29;
				break;
			}
			
			for ($i=$start; $i>=0; $i--) {
				switch ($this->timeInterval) {
					case 'hour':
						$nextDot = date('YmdH', strtotime('-'.$i.' hour', time()));
						$key = date('H:00', strtotime('-'.$i.' hour', time()));
					break;
					case 'day':
						$nextDot = date('Ymd', strtotime('-'.$i.' day', time()));
						$key = date('d', strtotime('-'.$i.' day', time()));					
					break;
				}
				
				foreach ($this->series as $serId => $series) {
					$this->data[$serId][$key] = (isset($order_totals[$nextDot][$serId])) ? $order_totals[$nextDot][$serId] : 0;
				}
			}
		}
		
		public function render()
		{
			$this->run();

			$categories = array_keys(current($this->data));
			$categories = "'" . implode("','", $categories) . "'";

			$out  = "{$this->id} = new Highcharts.Chart({";
			$out .= "chart: { renderTo: '{$this->id}', defaultSeriesType: '$this->type' },";
			$out .= "title: { text: '{$this->title}' },";
			$out .= "xAxis: { categories: [$categories] },";
			$out .= "yAxis: { title: { text: '{$this->yAxisTitle}' } },";
			$out .= "series: [";

			foreach ($this->data as $name=>$y) {
				$data = implode(',', $y);
				$serName = $this->series[$name];
				$out .= "{ data: [$data], name: '$serName' },";
			}
			$out .= "]";
			$out .= "});\n";
			
			echo $out;
		}
	}	
?>
<?PHP include('inc/header.inc.php'); ?>
        <div id="bd">
            <div id="yui-main">
                <div class="yui-b"><div class="yui-g">

                    <div class="block">
                        <div class="hd">
                            <h2>Your Applications</h2>
                        </div>
                        <div class="bd">
                            <table class="lines">
                                <thead>
                                    <tr>
                                        <td>Name</td>
                                        <td>Current Version</td>
										<td>Last Release Date</td>
										<td>Downloads / Updates</td>
										<td>Support Questions</td>
										<td>Bug Reports</td>
										<td>Feature Requests</td>
                                    </tr>
                                </thead>
                                <tbody>
									<?PHP foreach($apps as $a) : ?>
									<tr>
	                                    <td><a href="application.php?id=<?PHP echo $a->id;?>"><?PHP echo $a->name; ?></a></td>
	                                    <td><?PHP echo $a->strCurrentVersion(); ?></td>
										<td><?PHP echo $a->strLastReleaseDate(); ?></td>
										<td><a href="versions.php?id=<?PHP echo $a->id; ?>"><?PHP echo number_format($a->totalDownloads()); ?></a> / <a href="versions.php?id=<?PHP echo $a->id; ?>"><?PHP echo number_format($a->totalUpdates()); ?></a></td>
										<td><?PHP echo $a->numSupportQuestions(); ?></td>
										<td><?PHP echo $a->numBugReports(); ?></td>
										<td><?PHP echo $a->numFeatureRequests(); ?></td>
									</tr>
									<?PHP endforeach; ?>
                                </tbody>
                            </table>
						</div>
					</div>
					
    				<div class="block" style="float:left;margin-right:2em;width:100%;">
						<div class="hd">
							<h2>Download Activity</h2>
						</div>
						<div class="bd">
							<div id="chart_dl_24" class="chart"></div>
						</div>
						<div class="bd">
							<div id="chart_dl_30" class="chart"></div>
						</div>
					</div>
              
					<div class="block">
    					<div class="hd">
    						<h2>Recent Orders (<?PHP echo number_format(Order::totalOrders()); ?> total)</h2>
    					</div>
    					<div class="bd">
    					    <table class="lines">
    					        <thead>
    					            <tr>
    					                <td>Date</td>
    					                <td>Name</td>
    					                <td>Email</td>
    					                <td>App Name</td>
    					            </tr>
    					        </thead>
    					        <tbody>
        							<?PHP foreach($orders as $o) : ?>
        							<tr>
        							    <td><?PHP echo time2str($o->dt); ?></td>
        							    <td><a href="order.php?id=<?PHP echo $o->id; ?>"><?PHP echo utf8_encode($o->first_name); ?> <?PHP echo utf8_encode($o->last_name); ?></a></td>
        							    <td><a href="mailto:<?PHP echo $o->payer_email; ?>"><?PHP echo $o->payer_email; ?></a></td>
        							    <td><?PHP echo $o->applicationName(); ?></td>
        							</tr>
        							<?PHP endforeach; ?>
    					        </tbody>
    					    </table>
    					</div>
    				</div>
    				
                </div></div>
            </div>
            <div id="sidebar" class="yui-b">
				<div class="block">
					<div class="hd">
						Search Orders
					</div>
					<div class="bd">
						<form action="orders.php?id=<?PHP echo @$app_id; ?>" method="get">
							<p><input type="text" name="q" value="<?PHP echo @$q; ?>" id="q" class="text">
							<span class="info">Searches Buyer's Name and Email address.</span></p>
							<p><input type="submit" name="btnSearch" value="Search" id="btnSearch"> | <a href="order-new.php">Create Manual Order</a></p>
						</form>
					</div>
				</div>

				<div class="block">
					<div class="hd">
						<h2>Downloads 12 Hours</h2>
					</div>
					<div class="bd">
						<a href="<?PHP echo $opw24_fb->draw(false); ?>" class="fb"><?PHP $opw24->draw(); ?></a>
					</div>
				</div>

				<div class="block">
					<div class="hd">
						<h2>Downloads 15 Days</h2>
					</div>
					<div class="bd">
						<a href="<?PHP echo $opw30_fb->draw(false); ?>" class="fb"><?PHP $opw30->draw(); ?></a>
					</div>
				</div>				
				
				<div class="block">
					<div class="hd">
						<h2>Create an Application</h2>
					</div>
					<div class="bd">
						<form action="index.php" method="post">
		                    <p>
								<label for="test1">Application Name</label>
		                        <input type="text" class="text" name="name" id="appname" value="">
		                    </p>
							<p><input type="submit" name="btnNewApp" value="Create Application" id="btnNewApp"></p>
						</form>	
					</div>
				</div>

            </div>
        </div>

<?PHP include('inc/footer.inc.php'); ?>
<script type="text/javascript" charset="utf-8">
	$(document).ready(function() {
		Highcharts.theme = {
		   colors: ['#058DC7', '#50B432', '#ED561B', '#DDDF00', '#24CBE5', '#64E572', '#FF9655', '#FFF263', '#6AF9C4'],
		   chart: {
		      backgroundColor: {
		         linearGradient: [0, 0, 500, 500],
		         stops: [
		            [0, 'rgb(255, 255, 255)'],
		            [1, 'rgb(240, 240, 255)']
		         ]
		      },
		      borderWidth: 2,
		      plotBackgroundColor: 'rgba(255, 255, 255, .9)',
		      plotShadow: true,
		      plotBorderWidth: 1
		   },
		   title: {
		      style: {
		         color: '#000',
		         font: 'bold 16px "Trebuchet MS", Verdana, sans-serif'
		      }
		   },
		   subtitle: {
		      style: {
		         color: '#666666',
		         font: 'bold 12px "Trebuchet MS", Verdana, sans-serif'
		      }
		   },
		   xAxis: {
		      gridLineWidth: 1,
		      lineColor: '#000',
		      tickColor: '#000',
		      labels: {
		         style: {
		            color: '#000',
		            font: '11px Trebuchet MS, Verdana, sans-serif'
		         }
		      },
		      title: {
		         style: {
		            color: '#333',
		            fontWeight: 'bold',
		            fontSize: '12px',
		            fontFamily: 'Trebuchet MS, Verdana, sans-serif'
		
		         }
		      }
		   },
		   yAxis: {
		      minorTickInterval: 'auto',
		      lineColor: '#000',
		      lineWidth: 1,
		      tickWidth: 1,
		      tickColor: '#000',
		      labels: {
		         style: {
		            color: '#000',
		            font: '11px Trebuchet MS, Verdana, sans-serif'
		         }
		      },
		      title: {
		         style: {
		            color: '#333',
		            fontWeight: 'bold',
		            fontSize: '12px',
		            fontFamily: 'Trebuchet MS, Verdana, sans-serif'
		         }
		      }
		   },
		   legend: {
		      itemStyle: {
		         font: '9pt Trebuchet MS, Verdana, sans-serif',
		         color: 'black'
		
		      },
		      itemHoverStyle: {
		         color: '#039'
		      },
		      itemHiddenStyle: {
		         color: 'gray'
		      }
		   },
		   labels: {
		      style: {
		         color: '#99b'
		      }
		   }
		};
		
		// Apply the theme
		var highchartsOptions = Highcharts.setOptions(Highcharts.theme);
		
		<?PHP $chart_dl_24->render(); ?>
		<?PHP $chart_dl_30->render(); ?>
	});
</script>
