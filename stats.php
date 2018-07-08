<?PHP
	require 'includes/master.inc.php';
	$Auth->requireAdmin('login.php');
	$nav = 'stats';

	$db = Database::getDatabase();

	$applications = DBObject::glob('Application', 'SELECT * FROM shine_applications ORDER BY name');

	$chart_app_activity = new Chart();
	$chart_app_activity->id          = 'chart_app_activity';
	$chart_app_activity->type        = 'column';
	$chart_app_activity->title       = 'App Activity by Week';
	$chart_app_activity->yAxisTitle  = '# Sparkle Updates';
	$chart_app_activity->xColumnName = 'ywdt';
	$chart_app_activity->yColumnName = 'cnt';
	$where = (isset($_GET['id'])) ? " AND app_id='" . $_GET['id'] . "' " : "";
	$chart_app_activity->query        = 'SELECT COUNT(*) as cnt, YEARWEEK(dt, 3) as ywdt FROM `shine_sparkle_reports` rep WHERE DATE_ADD(dt, INTERVAL 16 WEEK) >= NOW() '.$where.' GROUP BY ywdt ORDER BY ywdt ASC';
	$chart_app_activity->query_unique = 'SELECT COUNT(DISTINCT CONCAT(DATE_FORMAT(dt,"%Y%m%d"), ip)) as cnt, YEARWEEK(dt, 3) as ywdt FROM `shine_sparkle_reports` rep WHERE DATE_ADD(dt, INTERVAL 16 WEEK) >= NOW() '.$where.' GROUP BY ywdt ORDER BY ywdt ASC';


	Class Chart
	{
		public $id;
		public $type;
		public $title;
		public $yearColumn;
		public $weekColumn;
		public $xColumnName;
		public $yColumnName;
		public $query;
		public $appID;
		public $yAxisTitle;

		private $data;

		private function getWeekStart($week, $year=""){
			$first_date = strtotime("1 january ".($year ? $year : date("Y")));
			if(date("D", $first_date)=="Mon") {
				$weekStart = $first_date;
			} else {
				$weekStart = strtotime("next Monday", $first_date)-604800;
			}
			$plus_week = "+".($week-1)." week";
			return strtotime($plus_week, $weekStart);
		}
		
		public function run()
		{
			$db = Database::getDatabase();

			$stats = array(
				'all' => array(),
				'unique' => array()
			);

			$rows = $db->getRows($this->query);
			foreach($rows as $row)
			{
				$x = $row[$this->xColumnName];
				$y = $row[$this->yColumnName];
				$stats['all'][$x] = $y;
			}
			
			$rows = $db->getRows($this->query_unique);
			foreach($rows as $row)
			{
				$x = $row[$this->xColumnName];
				$y = $row[$this->yColumnName];
				$stats['unique'][$x] = $y;
			}
			
			$this->data = array(
				'all' => array(),
				'unique' => array()
			);
			for ($i=15; $i>=0; $i--) {
				$nextWeek = date('oW', strtotime('-'.$i.' week', time()));
				$year = date('o', strtotime('-'.$i.' week', time()));
				$week = date('W', strtotime('-'.$i.' week', time()));
				$weekStart = $this->getWeekStart($week, $year);
				$key = date("M d", $weekStart) . " - " . date("M d", strtotime("+6 days", $weekStart));
				$this->data['all'][$key] = (isset($stats['all'][$nextWeek])) ? $stats['all'][$nextWeek] : 0;
				$this->data['unique'][$key] = (isset($stats['unique'][$nextWeek])) ? $stats['unique'][$nextWeek] : 0;
			}
		}
		
		public function render()
		{		
			$this->run();

			$categories = array_keys($this->data['all']);
			$categories = "'" . implode("','", $categories) . "'";
			$data = implode(',', $this->data['all']);
			$data_unique = implode(',', $this->data['unique']);

			$out  = "
			{$this->id} = new Highcharts.Chart({";

			$out .= "
			chart: {
                renderTo: '{$this->id}',
                type: 'column'
            },
            title: {
                text: '{$this->title}'
            },
            xAxis: [{
                categories: [$categories],
                //reversed: false
            },
            /*
            { // mirror axis on right side
                opposite: true,
                reversed: false,
                categories: [$categories],
                linkedTo: 0
            }
            */],
            yAxis: {
                title: {
                    text: '{$this->yAxisTitle}'
                },
/*
                labels: {
                    formatter: function(){
                        return (Math.abs(this.value) / 1000) + 'k';
                    }
                },
*/
                //min: -400000,
                //max: 400000
            },
            
            tooltip: {
                formatter: function(){
                    return '<b>'+ this.series.name +', '+ this.point.category +'</b><br/>'+
                        'Updates: '+ Highcharts.numberFormat(Math.abs(this.point.y), 0);
                }
            },
    /*
            plotOptions: {
                series: {
                    stacking: 'normal'
                }
            },
    */
            series: [{
                name: 'App updates',
                data: [$data]
            }, {
                name: 'Unique queries',
                data: [$data_unique]
            }]
			";
			$out .= "});";

			echo $out;
		}
	}
?>
<?PHP include('inc/header.inc.php'); ?>

        <div id="bd">
            <div id="yui-main">
                <div class="yui-b"><div class="yui-g">


                    <div class="block tabs spaces">
                        <div class="hd">
                            <h2>Sparkle Stats</h2>
							<ul>
								<li class="<?PHP if(!isset($_GET['id'])) echo 'active'; ?>"><a href="stats.php">All Apps</a></li>
								<?PHP foreach($applications as $a) : ?>
								<li class="<?PHP if(@$_GET['id'] == $a->id) echo 'active'; ?>"><a href="stats.php?id=<?PHP echo $a->id; ?>"><?PHP echo $a->name; ?></a></li>
								<?PHP endforeach; ?>
							</ul>
							<div class="clear"></div>
                        </div>
					</div>

					<div class="block" style="float:left;margin-right:2em;width:100%;">
						<div class="hd">
							<h2>App activity chart</h2>
						</div>
						<div class="bd">
							<div id="chart_app_activity" class="chart"></div>
						</div>
					</div>
              
                </div></div>
            </div>
            <div id="sidebar" class="yui-b">

            </div>
        </div>

<?PHP include('inc/footer.inc.php'); ?>
<script type="text/javascript" charset="utf-8">
	$(document).ready(function() {
		<?PHP $chart_app_activity->render(); ?>
	});
</script>
