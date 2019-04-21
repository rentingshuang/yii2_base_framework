<?php

use light\swagger\RRKDUIAsset;
use common\components\CommonFun;
use common\components\CommonApiHelper;

RRKDUIAsset::register($this);
 ?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>接口文档</title>
   	<?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<?php if(empty($cfg)):?>
	<div class="container bs-docs-container">SORRY,什么也没有。</div>
<?php else: ?>
<div class="container bs-docs-container">
		<div class="row">
			<div class="col-md-3">
				<div class="bs-sidebar hidden-print affix-top"  id="nav-bs-sidenav">
					<ul class="nav bs-sidenav navbar navbar-static">
						<li class="active"><a href="#functions">API介绍</a></li>
						<?php 
							$setHeader = CommonFun::getArrayValue($cfg['api'],'setHeader',false);
							if($setHeader):
						?>
						<li><a href="#setHeader">Header设置</a></li>
						<?php 
							endif;
						?>
						<?php 
							$apis = [];
							if(isset($cfg['groups'])):
								$commonResponse = CommonFun::getArrayValue($cfg['api'],'commonResponse',[]);
								$groups = isset ( $cfg ['api'] ) && isset ( $cfg ['api'] ['groups'] ) ? $cfg ['api'] ['groups'] : [ ];
								foreach ($cfg['groups'] as $groupsName => $groupItem):
									$isGroup = isset($groups[$groupsName]);
							?>
								<?php if($isGroup)://存在组 则把组加进来，下面的则自己循环?>
									<li val = <?=$groupsName?>><a href="javascript:void(0)"><?= (($groups[$groupsName]).$groupsName)?></a></li>
								<?php endif;?>
								
								<?php foreach ($groupItem as $u => $item):
										$apis[$u] = $item;
										$flag = $isGroup?('pid ='.$groupsName):'';
										$href = str_replace('/', '', ('#'.$u));
										$class= $isGroup?'':'';
										$txt  = ($isGroup?'&nbsp;&nbsp;- ':''). (isset($item['name'])?$item['name']:$item['desc']);
								?>
								<li <?=$flag?> class="<?= $class?>"><a href="<?=$href?>"><?= $txt?></a></li>
								<?php endforeach;?>		
						<?php 
							endforeach; 
						endif;
						?>
						<?php 
							$loadApiTool = isset($cfg['api']) && isset($cfg['api']['loadApiTool']) ? $cfg['api']['loadApiTool'] : 1;
							if($loadApiTool == 1):
						?>
							<li><a href="#getOtherApi">加载Api工具</a></li>
						<?php endif;?>
					</ul>
				</div>
			</div>
			
			<div class="col-md-9" data-spy="scroll" data-target="#nav-bs-sidenav" data-offset="50" >
				<!-- Getting started -->
				<div class="bs-docs-section">
					<div class="page-header">
						<h1 id="functions"><?= isset($cfg['api']) && isset($cfg['api']['name']) ? $cfg['api']['name']:'接口文档'; ?></h1>
					</div>
					<div class="bs-callout bs-callout-info">
						<h4>说明</h4>
						<p> 
                    	<?= isset($cfg['api']) && isset($cfg['api']['description'])?$cfg['api']['description']:'这家伙很懒，连说明都没有。'?>
                    </p>
					</div>
				</div>
				
				<?php if(isset($setHeader) && $setHeader):?>
				<div class="bs-docs-section">
	                <div class="page-header">
	                    <h1 id="setHeader">Header设置</h1>
	                </div>
	               
	                <p class="lead">
	                	说明： 设置请求header的参数，以分号(英文)连接。<br/>
	                	如：Content-Type: application/json;timestamp: 1474279837924;
	                </p>
	                <textarea class="form-control" rows="3" id="txt-headers" style="height:100px" placeholder="请设置header"></textarea>
	               	<p class="lead" />
					<p class="text-right">
						<button type="button" class="btn btn-primary" id="btn-setHeader">设置</button>
					</p>	
            	</div>
				<?php endif;?>

				<?php if($apis):
						foreach ($apis as $u=>$item):
				          $un = str_replace('/', '', $u);
						  $url = CommonFun::getArrayValue($item,'url',''); 	
				          $url = empty($url)?($hostInfo.CommonFun::url([$u])):$url; 
				?>
				<div class="bs-docs-section">
					<div class="page-header">
						<h1 id="<?= $un?>"><?= CommonFun::getArrayValue($item,'name',$item['desc']);?></h1>
					</div>
					<p class="lead">
						地址： <?= $url;?> <span class="label label-success"><?= CommonFun::getArrayValue($item,'method','post');?></span>
					</p>
					<p class="lead">说明：<?= CommonFun::getArrayValue($item,'desc','无');?></p>
					<p class="lead">入参：</p>
					<div class="table-responsive">
						<table class="table table-hover table-bordered">
							<thead>
								<tr>
									<th>参数名</th>
									<th>类型</th>
									<th>是否必填</th>
									<th>参数名说明</th>
									<th>备注</th>
								</tr>
							</thead>
							<tbody>
								<?php 
									$res = CommonApiHelper::createApiData(CommonFun::getArrayValue($item,'params',[]));
									$inputs = $res['json'];
									echo $res['html'];
								?>
							</tbody>
						</table>
					</div>
					
					<?php 
						$commonRes = isset($item['commonResponse']) ? $item['commonResponse'] : 1;
						$showDemo = isset($item['showDemo']) ? $item['showDemo'] : 1;
						if($commonRes == 1):
					?>
						<p class="lead">出参：</p>
						<div class="table-responsive">
							<table class="table table-hover table-bordered">
								<thead>
									<tr>
										<th>参数名</th>
										<th>类型</th>
										<th>是否必填</th>
										<th>参数名说明</th>
										<th>备注</th>
									</tr>
								</thead>
								<tbody>
									<?php 
										$res = CommonApiHelper::createApiData(CommonFun::getArrayValue($item,'response',[]),0,$commonResponse);
										echo $res['html'];
									?>
								</tbody>
							</table>
						</div>
					<?php endif;?>
					
					<?php if($showDemo==1):?>
						<p class="lead">请求示例：</p>
						<textarea class="form-control" rows="3" id="<?=$un?>-demo-data">{}</textarea>
						<?php if(!empty($inputs)):?>
						<script type="text/javascript">
							var vv = formatJson('<?= json_encode($inputs,JSON_UNESCAPED_UNICODE); ?>');
							vv = vv.replace(' //', '//');
							$("#<?=$un?>-demo-data").val(vv);
						</script>
						<?php endif;?>
						<p class="lead" />
						<p class="text-right">
							<button type="button" class="btn btn-primary" onclick="sendData('<?=$un?>')">试一下</button>
						</p>
						<p class="lead" />
						<div class="progress hidden" id="<?=$un?>-loading">
							<div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%"></div>
						</div>
						<input type="hidden" id="<?=$un?>-hdUrl" value="<?=$url?>">
						<div class="hidden" id="<?=$un?>-send-res">
							<p class="lead">请求信息：</p>
							<pre></pre>
							<p class="lead">请求结果：</p>
							<pre></pre>
						</div>
					<?php endif;?>
				</div>
				<?php endforeach; endif;?>
				
				
				
            	
			</div>
		</div>
	</div>


	<!-- Footer -->
	<footer class="bs-footer">
		<div class="container">
			<ul class="footer-links">
				<li>当前版本： v<?=CommonFun::getArrayValue($cfg['api'],'version',' unkonw')?></li>
				<li class="muted">·</li>
				<li><a href="?clearCache=1">清除缓存</a></li>
			</ul>
		</div>
	</footer>
	

	<script>
	
	var sendData = function(flag){
		var datas = $("#"+flag+'-demo-data').val(),
			url = $("#"+flag+'-hdUrl').val();
			dlg = $("#"+flag+'-loading'),
			alldata = {'postData':datas,'url':url,'method':1};
		dlg.removeClass('hidden');
		var resInput = $("#"+flag+'-send-res');
		resInput.addClass('hidden');
		$.post('<?= $baseUrl?>',alldata,function(res){
			dlg.addClass('hidden');
			if(!res.status){
				alert(res.msg||'NET ERROR');
				return false;
			}
			try{
				var js = JSON.parse(res.data),
				   html = process(js);
			}catch(exception){
				html = process(res.data);
			}
			resInput.removeClass('hidden').find('pre').html('<b>url：</b></br>&nbsp;'+res.url+'</br>'+'<b>header：</b></br>&nbsp;'+res.header).next().next().html(html);
		},'json');
	};

	var showHeader = function(){
		$("#txt-headers").val($.cookie('headers'));
	}
	var checked = function(){
		if($("#inputUrl").val() == ''){
			alert('请输入URL地址');
			return false;
		}
		return true;
	}
	
    !function ($) {
    	showHeader();
    	$("#btn-setHeader").click(function(){
    		$.cookie('headers',$("#txt-headers").val(),{ expires: 1000, path: '/' });
    		alert('设置成功');
        });
		
        
        /*
    	$("#nav-bs-sidenav li").click(function(){
    		var v = $(this).attr('val')||'',
    			fg = $(this).attr('fg')||0;
    		$(this).addClass('active').siblings().removeClass('active');
    		if(v != ''){
    			$(this).parent().find('[pid='+v+']')[fg == 0?'show':'hide'](50);
    			$(this).attr('fg',fg == 0 ? 1 : 0);
    		}
    	});
    	*/
    }(jQuery)
</script>
<?php endif;?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
