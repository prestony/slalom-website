
<?php 

// Start session
session_start();

//should be able to view companies, if one has logged in.
// Define login URL
$loginURL = "https://www.tendersoko.com/login";

// Check if user is not logged in, redirect to login page
if (!isset($_SESSION['login'])) {
    echo "You are not logged in.<br>";
    header("Location: $loginURL"); 
    exit();
}


    $tables = "`company`";
    $tablecolumns ="`company`.`id`, `company`.`logo` as `logo`, `company`.`title` as `company`, `sector`.`name` as `sector`, `country`.`name` as `country`"; // Include country name
    
    $tables .= " LEFT JOIN `sector` ON `sector`.`id` = `company`.`sector`";
    $tables .= " LEFT JOIN `country` ON `country`.`id` = `company`.`country`";


	$tablejoiners= array();

	$queryParams = array();

	if(isset($_POST['message-review'])){
		$delP = $DB->prepare("DELETE FROM `reviews` WHERE user = ? AND `company` = ?");
		$delP->execute(array($_SESSION['login']->id,$_POST['company']));

		$inst = $DB->prepare("REPLACE INTO `tendersoko`.`reviews` (`user`, `company`, `rating`, `review`, `anonymous`) 
			VALUES ( ?,?,?,?, ?)");

		$anon = isset($_POST['anon']) ? "yes" : "no";

		$inst->execute(array($_SESSION['login']->id,$_POST['company'],$_POST['rating'],$_POST['message-review'], $anon));
	}

	if(isset($_POST['review-id']) && $_POST['review-id']!=""){
		$inst = $DB->prepare("UPDATE `tendersoko`.`reviews` SET `rating`=?, `review`=?, `anonymous`= ?
		 WHERE id = ? AND `user` = ?");

		$anon = isset($_POST['anon']) ? "yes" : "no";
		$inst->execute(array($_POST['rating'],$_POST['message-review'], $anon, $_POST['review-id'], $_SESSION['login']->id));
	}
	
	if(isset($_SESSION['login'])){
    	$hasSubscriptionQ = $DB->prepare('SELECT * FROM orders WHERE `user` = ? AND NOW() BETWEEN `start` and `expiry` AND `approved` != "no"');
        $hasSubscriptionQ->execute(array($_SESSION['login']->id));
        $hasSubscription = $hasSubscriptionQ->rowCount() > 0 ? true : false;
	}

	if(!isset($uuid)){
?>
<title>TenderSoko | View Companies</title>
<meta name="description" content="Find Companies that post tenders everyday." />
<meta name="keywords" content="Tenders, Company Tenders, Companies tendering in Kenya" />
<div id="body">
	<div id="filters-holder">
		<div class="container">
			<div class="row">
				<div class="col-sm-2">
					<div class="form-group">
						<select id="category-filter" name="category-filter" class="form-control filter-item">
							<option value="">Select a Category...</option>
							<?php 
								$selectC = $DB->query("SELECT `id`, `name` FROM `categories`");
								$selectC->setFetchMode(PDO::FETCH_OBJ);
								while($sr = $selectC->fetch()){
									$sel = "";
									if(isset($params["category"])){
										$sel = $params["category"] == $sr->id? "selected" : "";
										if($sel == "selected"){
											$queryParams[] = $sr->id;
											$tablejoiners[] = "`categories`.`id` = ?";
										}
									}
							?>
									<option value="category:<?php echo removeSlash($sr->name).":".$sr->id; ?>" <?php echo $sel; ?>><?php echo $sr->name; ?></option>
							<?php
								}
							?>
						</select>
					</div>
				</div>
				<div class="col-sm-2"> <!--sector from here.-->
					<div class="form-group">
						<select id="category-filter" name="category-filter" class="form-control filter-item">
							<option value="">Select a Sector...</option>
							<?php 
								$selectS = $DB->query("SELECT `id`, `name` FROM `sector`");
								$selectS->setFetchMode(PDO::FETCH_OBJ);
								while($sr = $selectS->fetch()){
									if(isset($params["sector"])){
										$sel = $params["sector"] == $sr->id ? "selected" : "";
										if($sel == "selected"){
											$queryParams[] = $sr->id;
											$tablejoiners[] = "`sector`.`id` = ?";
										}
									}
							?>
									<option value="sector:<?php echo removeSlash($sr->name).":".$sr->id; ?>" <?php echo $sel; ?>><?php echo $sr->name; ?></option>
							<?php
								}
							?>
						</select>
					</div>
				</div> <!--sector ends here-->
				
				<!--add country-->
								<div class="col-sm-2">
					<div class="form-group">
						<select id="category-filter" name="category-filter" class="form-control filter-item">
							<option value="">Select a Country...</option>
							<?php 
								$selectS = $DB->query("SELECT `id`, `name` FROM `country`");
								$selectS->setFetchMode(PDO::FETCH_OBJ);
								while($sr = $selectS->fetch()){
									if(isset($params["country"])){
										$sel = $params["country"] == $sr->id ? "selected" : "";
										if($sel == "selected"){
											$queryParams[] = $sr->id;
											$tablejoiners[] = "`country`.`id` = ?";
										}
									}
							?>
									<option value="country:<?php echo removeSlash($sr->name).":".$sr->id; ?>" <?php echo $sel; ?>><?php echo $sr->name; ?></option>
							<?php
								}
							?>
						</select>
					</div>
				</div>
				<!--country to end here-->
				
				<div class="col-sm-4">
					<div class="form-group">
						<input type="text" class="form-control filter-item" id="company-name-search" name="company-name-search" placeholder="Search Company by name..." />
					</div>
				</div>
				<div class="col-sm-2">
					<div class="form-group">
						<button type="submit" class="btn btn-success" id="filter-companies-btn"><i class="glyphicon glyphicon-filter"></i> Filter Companies</button>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="container">
		<?php 

			$maximumRows = 50;
			$currentPage = 0;

			if(isset($page)){
				$currentPage = intval($page)-1;
			}
			else{
				$page = 1;
			}

			$lowerLim = $currentPage*$maximumRows;

			if(count($tablejoiners) > 0 ){
				$tablejoiners = " WHERE ".implode(" AND ", $tablejoiners);
			}
			else{
				$tablejoiners = "";
			}

			$selectCquery = "SELECT count(`company`.`id`) as `number` FROM $tables $tablejoiners";
			$CompanyNos = $DB->prepare($selectCquery);
			$CompanyNos->execute($queryParams);
			$CompanyNos->setFetchMode(PDO::FETCH_OBJ);
			$CompanyNo = $CompanyNos->fetch()->number;

			$maxPages = ceil($CompanyNo/$maximumRows);

			$prevPage = ($page - 1) <=1 ? 1 : ($page - 1);
			$nextPage = ($page + 1) >=$maxPages ? $maxPages : ($page + 1);

			$upperLim = ($lowerLim+$maximumRows) >= $CompanyNo ? $CompanyNo : ($lowerLim+$maximumRows);

			$selectCquery = "SELECT $tablecolumns FROM $tables $tablejoiners GROUP BY company.`title` ASC LIMIT $lowerLim, $maximumRows";

			$STnd = $DB->prepare($selectCquery);
			$STnd->execute($queryParams);
			$STnd->setFetchMode(PDO::FETCH_OBJ);
			$totalNowT = $STnd->rowCount();


			$url = removePage();
		?>
		<div id="tenders-list">
			<div class="col-sm-8">
				<div class="row fit-in">
					<div class="btn-group btn-group-sm pull-right" role="group">
						<a role="button" class="btn btn-danger" href="<?php echo $url; ?>/page/<?php echo $prevPage; ?>"><i class="glyphicon glyphicon-chevron-left"></i> <span class="hidden-xs hidden-sm">Prev</span></a>
						<select role="button" class="page-navigation-select btn btn-warning">
						<?php
							for($z = 0; $z < $maxPages; $z++){
								$active = $z == $currentPage ? "selected" : "";
						?>
							<option value="<?php echo $url; ?>/page/<?php echo $z+1; ?>" <?php echo $active; ?>><?php echo $z+1; ?></option>
						<?php
							}
						?>
						</select>
						<a role="button" class="btn btn-danger" href="<?php echo $url; ?>/page/<?php echo $nextPage; ?>"><span class="hidden-xs hidden-sm">Next</span> <i class="glyphicon glyphicon-chevron-right"></i></a>
					</div>
					<div class="pull-left">
						<h4><small><i class="glyphicon glyphicon-eye-open"></i> Viewing <?php echo $lowerLim+1; ?> - <?php echo $upperLim; ?> of <?php echo $CompanyNo; ?> Companies.</small></h4>
					</div>
				</div>
				<div class="row">
				<?php 
					if($totalNowT > 0){
						while($st = $STnd->fetch()){
					?>
					<div class="company-listing col-md-6 clearfix">
						<div class="col-sm-3 col-xs-4">
							<img class="logo img-responsive" src="images/companies/<?php echo $st->logo; ?>" />
						</div>
						<div class="col-sm-9 col-xs-8">
							<h2 class="new ellipsis">
								<a href="company/<?php echo removeSpace(removeSlash($st->company)); ?>-<?php echo $st->id; ?>/" target="_blank"><?php echo $st->company; ?></a> 
							</h2>
							<div class="row size-1">
								<b><i class="glyphicon glyphicon-briefcase"></i> Sector</b> : <a href="company/<?php echo $st->sector; ?>"><?php echo $st->sector; ?></a>
								<b><i class="glyphicon glyphicon-globe"></i> Country</b> : <?php echo $st->country; ?>
							</div>
						</div>
					</div>
				<?php
						}
				?>
				</div>
				<?php
					}
					else{
				?>
						<div class="alert alert-warning"><i class="glyphicon glyphicon-info-sign"></i> No Companies matching your criteria were found</div>
				<?php
					}
				?>

				<div class="row fit-in">
					<div class="btn-group btn-group-sm pull-right" role="group">
						<a role="button" class="btn btn-success" href="<?php echo $url; ?>/page/<?php echo $prevPage; ?>"><i class="glyphicon glyphicon-chevron-left"></i> <span class="hidden-xs hidden-sm">Prev</span></a>
						<select role="button" class="page-navigation-select btn btn-warning">
						<?php
							for($z = 0; $z < $maxPages; $z++){
								$active = $z == $currentPage ? "selected" : "";
						?>
							<option value="<?php echo $url; ?>/page/<?php echo $z+1; ?>" <?php echo $active; ?>><?php echo $z+1; ?></option>
						<?php
							}
						?>
						</select>
						<a role="button" class="btn btn-success" href="<?php echo $url; ?>/page/<?php echo $nextPage; ?>"><span class="hidden-xs hidden-sm">Next</span> <i class="glyphicon glyphicon-chevron-right"></i></a>
					</div>
				</div>
			</div>
			<div class="col-sm-4 fit-in large">
				<!--div class="panel panel-default">
				  <div class="panel-body">
				    Panel content
				  </div>
				  <div class="panel-footer">Panel footer</div>
				</div-->
			</div>
		</div>
	</div>
</div>
<!-- This is the potion that defines a tender view -->
<?php
}
else{
	$tables = "`company`";
    $tablecolumns ="count(`tenders`.`id`) as tenders, `company`.`id`, `company`.`logo` as `logo`, `company`.`title` as `company`, `categories`.`name` as `category`,`sector`.`name` as `sector`";
	$tables .= " LEFT JOIN `tenders` ON `tenders`.`company` = `company`.`id` LEFT JOIN `categories` ON `categories`.`id` = `company`.`category` LEFT JOIN `sector` ON `sector`.`id` = `company`.`sector`";

	$uuid = explode("-", $uuid);
	$tablejoiners[] = "`company`.`id` = ?";
	$companyID = array_pop($uuid);
	$companyTitle = implode(" ", $uuid);

	$updateView = $DB->prepare("UPDATE `company` SET `views`= `views` + 1 WHERE `id` = ?");
	$updateView->execute(array($companyID));
	ob_start();
?>

<title><?php echo $companyTitle; ?> | Company - TenderSoko</title>
<div id="body">
	<div class="f4f4f4">
		<div class="container">
			<h1 class="small-h1 blue">
				<div class="col-sm-7 ellipsis filter-bar-text share-title" data-toggle="tooltip" title="<?php echo $companyTitle; ?>" data-placement="bottom"><i class="fa fa-file-text"></i> <?php echo $companyTitle; ?></div>
				<div class="col-sm-5">
					<h5><div id="share-this-page"></div></h5>
				</div>
			</h1>
		</div>
	</div>
	<div class="container">
<?php
			$tablejoiners = " WHERE ".implode(" AND ", $tablejoiners);
			$tablecolumns .=",`company`.`website`,`company`.`latitude`,`company`.`longitude`,`company`.`description`,`company`.`views`";
			$selectCquery = "SELECT $tablecolumns FROM $tables $tablejoiners GROUP BY `id`";

			$STnd = $DB->prepare($selectCquery);
			$STnd->execute(array($companyID));
			$tenderNo = $STnd->rowCount();
			$STnd->setFetchMode(PDO::FETCH_OBJ);

			if($STnd->rowCount() == 0){
				header("Location: ".$BASE."company");
			}

			while($rt = $STnd->fetch()){
?>
			<div class="f4f4f4 bd-eaeaea">
				<div class="tender-info row">
					<div class="company-pic col-md-2 col-sm-4 text-center">
						<img src="images/companies/<?php echo $rt->logo; ?>" style="margin-right: auto; margin-left: auto;" 
						class="img-responsive text-center" align="center" />
						<?php
							if(isset($_SESSION['login']->level) && $_SESSION['login']->level=="admin"){
							    
						?>	
						<div style="padding:10px" class="text-center">
							<div class="btn btn-primary" data-toggle="modal" data-target="#add-company-logo">
								<i class="fa fa-upload"></i> Add Company Logo
							</div>
						</div>
						<?php
							}
						?>
					</div>
					<div class="col-md-6 col-sm-8">
						<?php
							$getRatings = $DB->prepare("SELECT COUNT(id) as `users`, AVG(rating) as `rating` FROM reviews WHERE company = ?");
							$getRatings->execute(array($companyID));
							$REVIEW = array();

							while($gR = $getRatings->fetch()){
								$REVIEW = array("users"=>$gR['users'], "rating"=>floatval($gR['rating']));
							}
						?>
						<h4 class="row">
							<div class="col-xs-12 ellipsis">
								<i class="glyphicon glyphicon-folder-close" data-toggle="tooltip" title="Company"></i> 
								<?php echo $rt->company; ?>
							</div>											
							<div class="col-xs-12" style="padding-top: 10px;" >
								<div class="pull-left">
									<span class="label label-success">
										<i class="fa fa-star"></i>
										<?php echo number_format($REVIEW['rating'],1); ?>
									</span>								
								</div>
								<?php
									$remainingNo = 1-(ceil($REVIEW['rating'])-$REVIEW['rating']);
									for($z=0; $z<5; $z++){
										$df = $REVIEW['rating'] - $z;
										$no = $df > 1 ? "" : ($df > 0 ? "-half-o" : "-o") ;
								?>	
								<div class="pull-left ppointed" 
									data-toggle="modal" data-target="#review-company"
									onclick="addRating('<?php echo $z; ?>')">
									<i class="fa fa-star<?php echo $no; ?>" style="padding: 2px 4px 6px; color:#FA5007 !important"></i>
								</div>
								<?php
									}
								?>
								<div class="pull-left" style="padding: 2px 4px 6px">
									<em onclick="viewReviews()"><small>
										<a class="blue">
											<small><i class="fa fa-users"></i></small> 
											<?php echo number_format($REVIEW["users"]); ?> reviews
										</a>
									</small></em>
								</div>
							</div>
						</h4>
						<p><?php echo $rt->description == "" ? "No Description Provided." : $rt->description; ?></p>
						<div class="col-sm-12 ellipsis font-14">
							<h4><i class="glyphicon glyphicon-link green"></i> Website : <a href="//<?php echo $rt->website; ?>" target="_blank"><?php echo $rt->website; ?></a></h4>
						</div>
						<div class="col-xs-6 ellipsis font-14">
							<h4><i class="glyphicon glyphicon-tag purple"></i> Sector : <?php echo $rt->sector; ?></h4>
						</div>
						<div class="col-xs-6 ellipsis font-14">
							<h4><i class="glyphicon glyphicon-tag blue"></i> Category : <?php echo $rt->category; ?></h4>
						</div>
						<div class="col-xs-6 ellipsis font-14">
							<h4><i class="glyphicon glyphicon-book gold"></i> <?php echo $rt->tenders; ?> Tenders</h4>
						</div>
						<div class="col-xs-6 ellipsis font-14">
							<h4><i class="glyphicon glyphicon-eye-open gray"></i> <?php echo intval($rt->views); ?> Views</h4>
						</div>
					</div>
					<div class="col-md-4 col-sm-12">
						<div id="small-map"></div>
					</div>
				</div>
				<div>
					<div style="background: #6FC3ED; color: #232323; text-align: center">
						<button class="no-rd btn btn-primary" onclick="viewReviews()">Read Reviews (<?php echo number_format($REVIEW["users"]); ?>) <i class="fa fa-chevron-down rev-view-ic"></i></button>
					</div>
					<div class="padding-style row hidden" id="reviews-list">
						<?php 
							$getRevs = $DB->prepare("SELECT `reviews`.id, `reviews`.review, reviews.`user`, `reviews`.rating, `reviews`.anonymous, `users`.`first_name`, `users`.second_name FROM `reviews`, `users` 
								WHERE reviews.`user` = `users`.`id` 
								AND `reviews`.`company` = ?");
							$getRevs->execute(array($companyID));
							$revs = array();
							while($r = $getRevs->fetch()){
								$revs[] = $r;
							}	

							if(count($revs) == 0){
						?>
							<em class="text-center">Sorry this company has no reviews yet. </em>
						<?php
							}

							foreach ($revs as $rev) {
								if($rev['anonymous'] == "yes"){
									$rev['first_name'] = "Anonymous";
									$rev['second_name'] = "User";
								}
						?>
							<div class="col-sm-6 col-md-4 row">
								<div class="pull-left padding-style">
									<div class="profileIn">
										<div class="profileInNm"><?php echo substr($rev['first_name'][0], 0,1)."".substr($rev['second_name'][0], 0,1); ?></div>
									</div>
								</div>
								<div class="review-rest">
									<h4 class="gray">
										<b class="padding-right:20px"><?php 
											echo $rev['first_name']." ".$rev['second_name'];
										?>
										</b>
										<?php 
											for($e=0; $e<intval($rev['rating']); $e++){
										?>
											<i class="fa fa-star gold"></i>
										<?php
											}
										?>
										<?php 
											if(isset($_SESSION['login']->id) && ($rev['user']==$_SESSION['login']->id)){
										?>
											<small class="pull-right ppointed review-edit"
											data-toggle="modal" data-target="#review-company"
											 data-review='<?php echo json_encode($rev); ?>' onclick="editReview(this)"> <i class="fa fa-pencil"></i> Edit</small>
										<?php
											}
										?>
									</h4>
									<p><em><?php echo $rev['review']; ?></em></p>
								</div>
							</div>
						<?php
							}
						?>
					</div>
				</div>
			</div>

			<div class="">
				<h4 class="panel-heading text-primary f4f4f4">
					<div style="overflow:hidden; text-overflow: ellipsis; white-space: nowrap;">
						Tenders By <?php echo $companyTitle; ?>			
					</div>						
				</h4>
				<?php
					
					$Ttables = "`tender_type`,`categories`,`sector`,`company`, `tenders`";
					$Ttablecolumns ="`tenders`.`id`,`tenders`.`title`,`tenders`.`published`,`tenders`.`closing_date`,`tenders`.`views`,`tender_type`.`name` as `type`,`company`.`title` as `company`, `company`.`id` as `cid`, `categories`.`name` as `category`,`sector`.`name` as `sector`, GROUP_CONCAT(`tags`.`name` SEPARATOR ',') as `tags`";
                    $Ttablecolumns = "tenders.*, status.status_name";
                    $Ttables .= " LEFT JOIN status ON tenders.status = status.id";
                    
					$Ttables .= " LEFT JOIN `tagged_tenders` ON `tenders`.`id` = `tagged_tenders`.`tender` 
					LEFT JOIN `tags` ON `tags`.`id` = `tagged_tenders`.`tag` ";

					$Ttablejoiners= array("`categories`.`id` = `tenders`.`category`", "`sector`.`id` = `tenders`.`sector`","`tender_type`.`id` = `tenders`.`type`","`company`.`id` = `tenders`.`company`");

					$Ttablejoiners[] = "`company`.`id` = ?";

					$Ttablejoiners = " WHERE ".implode(" AND ", $Ttablejoiners);

					$selQ = "SELECT $Ttablecolumns FROM $Ttables $Ttablejoiners GROUP BY `tenders`.`id` DESC ";
					
					$Ctenders = $DB->prepare($selQ);
					$Ctenders->execute(array($companyID));
					$Ctenders->setFetchMode(PDO::FETCH_OBJ);


					if($Ctenders->rowCount() == 0){
				?>
						<div class="alert alert-info "><span class="text-danger"><i class="fa fa-exclamation-triangle"></i> No Tenders by this company yet.</span></div>
				<?php
					}
					else{
						while($Ct = $Ctenders->fetch()){
							$tagLinks = array();
							$tL = explode(",",$Ct->tags);
							foreach ($tL as $t) {
								$tagLinks[]=' <a href="tenders/tag:'.$t.'">'.$t.'</a>';
							}
							
							if(!isset($_SESSION['login']) || (isset($hasSubscription) && !$hasSubscription)){
							    $partsTitle = explode(" ", $Ct->title);
							    $Ct->title = $partsTitle[0].' '.$partsTitle[1].'... ';
							}
							
				?>
						<div class="tender-listing">
							 <h2 class="new">
							    <?php 
							        if(isset($_SESSION['login'])){
							            if($hasSubscription){
    							?>
    								<a href="tender/<?php echo removeSpace(removeSlash($Ct->title)); ?>-<?php echo $Ct->id; ?>/" target="_blank">
    								    <?php echo $Ct->title; ?>
    								</a> 
    								<?php } else { ?>
								    <a href="tender/<?php echo removeSpace(removeSlash($Ct->title)); ?>-<?php echo $Ct->id; ?>/" target="_blank">
    								    <?php echo $Ct->title; ?> <span style="color:#0d625a">&nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-info-circle"></i> Subscribe to View the tender details</span>
    								</a> 
								<?php   }
							     }
							     else{
							         ?>
							            <a href="tender/<?php echo removeSpace(removeSlash($Ct->title)); ?>-<?php echo $Ct->id; ?>/" target="_blank">
    								    <?php echo $Ct->title; ?> <span style="color:#0d625a">&nbsp;&nbsp;&nbsp;&nbsp;<i class="fa fa-info-circle"></i> Click to view the tender details</span>
    								</a> 
							         <?php
							     }
								?>
							</h2>  
							<div class="row size-1">
								<div class="col-sm-6">
									<b><i class="glyphicon glyphicon-briefcase"></i> Company</b> : <a href="company/<?php echo removeSlash($Ct->company); ?>-<?php echo $Ct->cid; ?>"><?php echo $Ct->company; ?></a>
								</div>
								<div class="col-sm-6">
									<b><i class="fa fa-calendar"></i> Closing Date</b> : <?php echo $Ct->closing_date; ?> </span>
								</div>
							</div>
							<div class="row size-2">
								<div class="col-sm-12">
								    <b><i class="glyphicon glyphicon-question-sign"></i> Status</b> : <?php echo $Ct->status_name; ?>,
									<b><i class="glyphicon glyphicon-tag"></i> Sector</b> : <?php echo $rt->sector; ?> 
									<b><i class="glyphicon glyphicon-tag"></i> Category</b> : <a href="tenders/category:<?php echo $Ct->category; ?>"><?php echo $Ct->category; ?></a>,
									<b><i class="glyphicon glyphicon-tags"></i> Others</b> - <?php echo implode(",", $tagLinks); ?> 
								</div>
							</div>
						</div>
				<?php
						}
					}
				?>
			</div>
<?php
			}
?>
	</div>
</div>

<div class="modal fade" tabindex="-1" role="dialog" id="review-company">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
				<span aria-hidden="true"><i class="fa fa-times"></i> </span></button>
				<h4 class="modal-title text-muted fa-2x thin"><i class="fa fa-star green"></i> Review this Company</h4>
			</div>
			<div class="modal-body">
				<?php
					if(isset($_SESSION["login"])){
				?>
					<form class="form" role="form" method="post">
						<div class="form-group text-left" id="ratings-number">
							<i class="fa gold ppointed" style="padding: 4px; font-size: 30px" data-rate="1"></i>
							<i class="fa gold ppointed" style="padding: 4px; font-size: 30px" data-rate="2"></i>
							<i class="fa gold ppointed" style="padding: 4px; font-size: 30px" data-rate="3"></i>
							<i class="fa gold ppointed" style="padding: 4px; font-size: 30px" data-rate="4"></i>
							<i class="fa gold ppointed" style="padding: 4px; font-size: 30px" data-rate="5"></i>
						</div>
						<div class="form-group">
							<input type="hidden" name="review-id" id="review-id">
							<label for="message-review" class="control-label">Review Message (Optional)</label>
							<textarea class="form-control" name="message-review" id="message-review" placeholder="Review Message..."></textarea>
						</div>
						<div class="form-group">
							<label for="anonymous" class="control-label">
								<input type="checkbox" name="anon" id="anon" />
								Review Anonymously
							</label>
						</div>
						<div class="form-group">
							<input type="hidden" name="rating" id="rating-review">
							<input type="hidden" name="company" id="companyID" value="<?php echo $companyID ?>">
							<button class="btn btn-danger" id="submit-rev"><i class="fa fa-send"></i> Submit Review</button>
							<a class="btn btn-warning" id="submit-rev" onclick="location.reload()"><i class="fa fa-times-circle"></i> Reset</a>
						</div>
					</form>
				<?php
					}
					else{
				?>
						<div class="alert alert-info">
							<i class="fa fa-2x fa-exclamation-triangle red"></i> Oops! You need to be <a href="login"><b>logged in</b></a> to review.
						</div>
				<?php
					}
				?>
					
			</div>
		</div>
	</div>
</div>

<?php
	//TODO connect this to payments and document availability to ensure direct download if user has paid for the document.
}
?>
<script type="text/javascript" src="//maps.google.com/maps/api/js?key=AIzaSyANZhnlgEQgqkgX6YMVTuwW-YjdLV68g2Y"></script>
<script type="text/javascript">
	var addRating = function(rt){
		rt = parseInt(rt)+1;
		setRating(rt);
	}

	var editReview = function(th){
		var rev = $(th).data("review");
		$("#message-review").val(rev.review);
		$("#review-id").val(rev.id);
		console.log(rev.anonymous);
		$("#anon").attr("checked", rev.anonymous=="yes" ? true : false);
		$("#submit-rev").html('<i class="fa fa-pencil"> Submit Edit</i>').toggleClass("btn-danger btn-primary");
		setRating(rev.rating)
	}


	var setRating = function(rt){
		$("#rating-review").val(rt);

		$("#ratings-number").empty()

		for(var z=0; z<5; z++){
			var df = rt-z;
			var cls = df >= 1 ? 'fa-star' : (df > 0 ? 'fa-star-half-o' : 'fa-star-o');
			var pp = '<i class="fa '+cls+' gold ppointed" style="padding: 4px; font-size: 30px" data-rate="'+(z+1)+'"></i>';

			$("#ratings-number").append(pp);
		}

		$("#ratings-number").off("click")
		$("#ratings-number").on("click", '.ppointed', function(){
			setRating(parseInt($(this).data("rate")));
		})
	}

	var viewReviews = function(){
		$("#reviews-list").toggleClass("hidden");
		$(".rev-view-ic").toggleClass("fa-chevron-down fa-chevron-up")
	}
</script>