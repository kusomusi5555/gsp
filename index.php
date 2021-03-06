<?php
require('function.php');
$user_id = $_SESSION['id'];
$page = $_REQUEST['page'];

//ページ指定がない場合は1ページ目を指定
if ($page == '') {
	$page = 1;
}

//ページが1以上のページを指定していたら、$pageを上書き
$page = max($page,1);

//ツイートの総件数を取得
$cnt = getPageCount();

//ツイートの総件数を5で割り小数点を切り捨てた値を代入
$maxPage = ceil($cnt['cnt'] / 5);

$page = min($page, $maxPage);

$start = ($page -1) * 5;

//ページ数を元に、ツイートを取得
$posts = getPostAll($start);

//自分がいいねしたツイートIDを全件取得
$check = getFavoriteAll($user_id);

?>

<?php require('header.php'); ?>
	<div class="container">
		<div class="row">
			<div class="col-md-8 offset-md-2">

			<div class="wrapper"></div>

			<?php foreach ($posts as $post): ?>
			<div class="card mb-5">
				<div class="card-body">
					<div class="d-flex">
						<img src="user_img/<?php echo h($post['user_img']); ?>" class="rounded-circle mt-4 ml-3" alt="プロフィール画像">
						<h5 class="card-title mt-5 ml-3 mr-md-auto">
							<a href="myPage.php?myPage_id=<?php echo $post['user_id'];?>" class="mr-5 name"><?php echo h($post['name']);?></a>
						</h5>
						<small class="text-muted mr-5 mt-5 date"><?php echo $post['created'];?></small>
					</div>
					<p class="card-text ml-5 mt-3"><a href="show.php?id=<?php echo $post['id']; ?>#<?php echo $post['id']; ?>">
					<?php echo nl2br(h($post['message']));?></a></p>
				</div>
				<?php if(isset($post['picture'])): ?>
					<img src="picture/<?php echo h($post['picture']);?>" class="main_picture mt-4" alt="投稿画像">
				<?php endif; ?>
				<div class="d-flex justify-content-end">
					<?php in_array($post['id'], $check) ? print'<a href="favorite_delete.php?id='.$post['id'].'" class="fas fa-heart fa-2x mt-3 mr-2 good"></a><span class="mt-3 good_count">'.$post['good'].'</span>'
					:print'<a href="favorite.php?id='.$post['id'].'" class="far fa-heart fa-2x mt-3 mr-2 good"></a><span class="mt-3 good_count">'.$post['good'].'</span>';?>
					<a href="post.php?id=<?php echo $post['id']; ?>" class="btn btn-dark mt-3 ml-3 mr-3 mb-3" id="re_message">返信</a>
					<?php if($post['user_id'] == $user_id): ?>
						<a href="delete.php?id=<?php echo $post['id']; ?>" class="btn btn-dark mt-3 mr-3 mb-3 delete">削除</a>
					<?php endif;?>
				</div>
			</div>
			<?php endforeach; ?>



			<ul>
			<?php if ($page > 1): ?>
			<li><a href="index.php?page=<?php echo ($page-1); ?>">
			前のページへ</a></li>
			<?php else: ?>
			<li>前のページへ</li>
			<?php endif; ?>
			<?php if ($page < $maxPage): ?>
			<li><a href="index.php?page=<?php echo ($page+1); ?>">
			次のページへ</a></li>
			<?php else: ?>
			<li>次のページへ</li>
			<?php endif; ?>
			</ul>
			</div>
			<a href="post.php"><i class="fas fa-pen-square fa-5x write"></i></a>
		</div>
	</div>
<?php require('footer.php'); ?>
