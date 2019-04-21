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
<title>项目配置</title>
   	<?php $this->head() ?>
</head>
<style>
.table .center { 
	text-align: center;
	vertical-align: middle!important;
}
#result img{float:none;margin:5px 10px}
 </style>
<body>
<?php $this->beginBody() ?>

<?php if(empty($cfg)):?>
	<div class="container bs-docs-container">SORRY,什么也没有。</div>
<?php else: ?>
	<script type="text/javascript">
	var check = function(type){
		var rd = Math.random();
		var obj = $("#res"+type);
		obj.html('<label>检测中...</label>');

		$.ajax({
			  url:'',
			  type:"POST",
			  data:{type:type,'rd':rd},
			  timeout:300000,
			  dataType:"json",
			  success:function(res){
				  if(res && !res.status){
						obj.html('<label class="red">×'+ (res.msg||'NET ERROR')+'</label>');
						return false;
					}
					var fn = 'check("'+type+'")';
					obj.html("<label style='color:#2e9445'>√ 成功</label>");
			  },
			  error:function(res){
				  obj.html('<label class="red">×' + res.responseText + '</label>');
				}
			});

	}
	</script>
	<div class="container bs-docs-container">
		<div class="row">
		
			<div class="col-md-12" data-spy="scroll" data-offset="50" >
				<!-- Getting started -->
				<div class="bs-docs-section">
					<div class="page-header">
						<h1 id="functions">项目配置项</h1>
					</div>
					<div class="bs-callout bs-callout-info">
						<h4>说明</h4>
						<p>
							<b>Params：</b>为项目业务参数项,如业务接口版本：ApiVersion、LogDir 等配置。</br>
							<b>Components：</b>为组件级参数项,如数据库：db、cache、mongodb 等配置。
						</p>
					</div>
				</div>
			
				<div class="table-responsive">
						<p><b>核心组件</b></p>
						<table class="table table-hover table-bordered" style=" table-layout: fixed;">
							<thead>
								<tr>
									<th width="10%">类型</th>
									<th width="60%">配置</th>
									<th width="20%" class="center">连接是否异常</th>
									<th width="10%">操作</th>
								</tr>
							</thead>
							<tbody>
							<?php 
								$components = $cfg['Components'];
								foreach ($components as $k=>$item):
									$isNeed = false;
									$config = '';
									$arr = ['db','ldpdb','redis','mongodb','gearman','queue'];
									if(in_array($k, $arr)){
										$isNeed = true;
										$config = $item;
									}
								?>
								<?php if($isNeed):?>
									<tr>
										<td><?= $k?></td>
										<td style="word-wrap:break-word;"><?= json_encode($config)?></td>
										<td id="res<?= $k?>"  class="center" style="word-wrap:break-word;"></td>
										<script>
											check('<?= $k?>');	
										</script>
										<td  class="center"><a href='javascript:;' onclick="check('<?= $k?>')" >重新检测</a></td>
									</tr>
								<?php endif;?>
							<?php endforeach;?>
							</tbody>
						</table>
					</div>
					<div id='result'>
						<p><b>项目配置</b></p>
						<pre style="font-size: 13px;font-family:'ff-tisa-web-pro-1','ff-tisa-web-pro-2','Lucida Grande','Helvetica Neue',Helvetica,Arial,'Hiragino Sans GB','Hiragino Sans GB W3','Microsoft YaHei UI','Microsoft YaHei','WenQuanYi Micro Hei',sans-serif;"></pre>
					</div>
					<p></p>
					<p><b>PHPINFO</b></p>
					<div>
						<?php phpinfo();?>
					</div>
			<div>
			<script>
				
				$(function(){
					try{
						var js = <?= json_encode($cfg)?>,
						 html = process(js);
					}catch(exception){
						alert('解析错误!');
						return false;
					}
					var resInput = $("#result"); 
					resInput.find('pre').html(html).find('img').eq(0).click();
						
				});
			 
			</script>
	</div>

<?php endif;?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
