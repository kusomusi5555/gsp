<?php
session_start();
header('Expires:-1');
header('Cache-Control:');
header('Pragma:');
session_regenerate_id(true);

//エスケープ処理
function h($str, $encode='UTF-8'){
    return htmlspecialchars($str, ENT_QUOTES, $encode);
}

//=====================================
//定数
//=====================================
//エラーメッセージ用定数
define('MSG01', 'Eメールの形式で入力して下さい');
define('MSG02', 'このメールアドレスは既に登録されています');
define('MSG03', '画像はjpg,ping,gif,jpegの形式でご指定ください');
define('MSG04', '入力必須です');
define('MSG05', 'メッセージの投稿に失敗しました');
define('MSG06', '半角英数字で入力をしてください');
define('MSG07', 'パスワードは８文字以上の半角英数字で入力をしてください');
define('MSG08', '正しいパスワードをご入力ください。');


$error = array();

//email形式チェック
function validEmailType($email){
    global $error;
    if(!filter_var($email, FILTER_VALIDATE_EMAIL )) {
        $error['email'] = MSG01;
    }
}

//email重複チェック
function validEmailDup($email){
    global $error;
    try {
        $dbh = dbConnect();
        $sql = 'SELECT COUNT(*) AS cnt FROM users WHERE email = :email';
        $data = array(':email' => $email);
        $stmt = queryPost($dbh, $sql, $data);
        $result = $stmt->fetch();
        if (($result['cnt']) > 0) {
            $error['email'] = MSG02;
        }
    } catch (Exception $e) {
        $error['common'] = MSG03;
        echo 'DB接続エラー: ' . $e->getMessage();  
    }
}

//画像拡張子を確認
function imagetype($fileName){
    global $error;
    if(!preg_match( "/.*?\.jpg|.*?\.png||.*?\.gif.*?\.jpeg/i", $fileName)){
       return $error['image'] = MSG02;
    }
}

//未入力チェック
function validRequired($str, $key){
    if ($str === '') {
        global $error;
        $error[$key] = MSG04;
    }
}

//半角英数字チェック
function halfAlphanumericCheck($key) {
    if (preg_match("/^[a-zA-Z0-9]+$/", $key) == false) {
        global $error;
        $error['Alphanumeric'] = MSG06;
    }
}

//パスワード最小文字数チェック
function stringMinSizeCheck($key,$min_size = 8){
    if (mb_strlen($key) < $min_size) {
        global $error;
        $error['MinSizeCheck'] = MSG07;
    }
} 

//エラーメッセージ取得
function getErrMsg($key){
    global $error;
    if (!empty($error[$key])) {
        return $error[$key];
    }
}

//データベース取得
function dbConnect(){
    $dsn = 'mysql:dbname=ngss;host=localhost;port=8889;charset=utf8';
    $user = 'root';
    $password = 'root';
    $options = array(PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC);
        $dbh = new PDO($dsn, $user, $password, $options);
        return $dbh;
}

//SQL実行
function queryPost($dbh, $sql, $data){
    $stmt = $dbh->prepare($sql);
    if (!$stmt->execute($data)) {
        return 0;
    }
    return $stmt;
}

//投稿総件数取得
function getPageCount(){
    try {
        $dbh = dbConnect();
        $sql = 'SELECT COUNT(*) AS cnt FROM posts';
        $data = array();
        $stmt = queryPost($dbh, $sql, $data);
            return $stmt->fetch();
    } catch(PDOException $e) {
        echo 'DB接続エラー: ' . $e->getMessage();
    }
}

//投稿全件から5件ごとに取得
function getPostAll($data){
    try {
        $dbh = dbConnect();
        $sql = 'SELECT u.id, u.name, u.user_img, p.*, COUNT(f.user_id)  AS good FROM users u RIGHT JOIN posts p 
        ON u.id=p.user_id LEFT JOIN favorite f ON p.id = f.post_id GROUP BY p.id ORDER BY p.created DESC LIMIT :limit,5';
        $stmt = $dbh->prepare($sql);
        $stmt->bindValue(':limit', (int)$data, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    } catch(PDOException $e) {
        echo 'DB接続エラー: ' . $e->getMessage();
    }
    
}

//いいねしたメッセージ全件の投稿IDを配列に挿入
function getFavoriteAll($user_id){
    try {
        $dbh = dbConnect();
        $sql = 'SELECT post_id FROM favorite WHERE user_id = :user_id';
        $data = array(':user_id' => $user_id);
        $stmt = queryPost($dbh, $sql, $data);

        while($favorite_check = $stmt->fetch()){
            $check[]=$favorite_check['post_id'];
        }
        return $check;
    } catch(PDOException $e) {
        echo 'DB接続エラー: ' . $e->getMessage();
    }
}

//自分がいいねしているかチェック
function getFavorite($user_id,$re_id){
    try {
        $dbh = dbConnect();
        $sql = 'SELECT * FROM favorite WHERE post_id=? AND user_id=?';
        $data = array($user_id,$re_id);
        $stmt = queryPost($dbh, $sql, $data);
        return $stmt->fetch();

    } catch(PDOException $e) {
        echo 'DB接続エラー: ' . $e->getMessage();
    }
}

//マイページで自分がフォローしているかを取得
function getFollow($user_id, $re_id){
    try {
        $dbh = dbConnect();
        $sql = 'SELECT * FROM follow WHERE user_id = :user_id AND follow = :follow';
        $data = array(':user_id' => $user_id, ':follow' => $re_id);
        $stmt = queryPost($dbh, $sql, $data);
        return $stmt->fetch();
    } catch(PDOException $e) {
        echo 'DB接続エラー: ' . $e->getMessage();
    }
}

//フォロー一覧を取得
function getFollowView($re_id){
    try {
        $dbh = dbConnect();
        $sql = 'SELECT u.id, u.name, u.user_img, f.* FROM users 
        u, follow f WHERE u.id=f.follow AND f.user_id=? ORDER BY f.created DESC';
        $data = array($re_id);
        $stmt = queryPost($dbh, $sql, $data);
        return $stmt;

    } catch(PDOException $e) {
        echo 'DB接続エラー: ' . $e->getMessage();
    }
}

//フォロワー一覧を取得
function getFollowerView($re_id){
    try {
        $dbh = dbConnect();
        $sql = 'SELECT u.id, u.name, u.user_img, f.* FROM users 
        u, follow f WHERE u.id=f.user_id AND f.follow=? ORDER BY f.created DESC';
        $data = array($re_id);
        $stmt = queryPost($dbh, $sql, $data);
        return $stmt;

    } catch(PDOException $e) {
        echo 'DB接続エラー: ' . $e->getMessage();
    }
}

//自分の全フォロー一覧取得
function getFollowAll($user_id){
    try {
        $dbh = dbConnect();
        $sql = 'SELECT follow FROM follow WHERE user_id=?';
        $data = array($user_id);
        $stmt = queryPost($dbh, $sql, $data);

        while($result = $stmt->fetch()){
            $check[]=$result['follow'];
        }
        return $check;
    } catch(PDOException $e) {
        echo 'DB接続エラー: ' . $e->getMessage();
    }
}

//各ユーザーの登録情報取得
function getUser($re_id){
    try {
        $dbh = dbConnect();
        $sql = 'SELECT * FROM users WHERE id = :re_id';
        $data = array(':re_id' => $re_id);
        $stmt = queryPost($dbh, $sql, $data);
        return $stmt->fetch();
            
    } catch(PDOException $e) {
        echo 'DB接続エラー: ' . $e->getMessage();
    }
}
//各ユーザーの投稿情報取得
function getUserContents($re_id){
    try {
        $dbh = dbConnect();
        $sql = 'SELECT u.name, u.user_img, p.*, COUNT(f.user_id)  AS good FROM users u RIGHT JOIN posts p 
        ON u.id=p.user_id LEFT JOIN favorite f ON p.id = f.post_id WHERE u.id = ? GROUP BY p.id ORDER BY p.created DESC';
        $data = array($re_id);
        $stmt = queryPost($dbh, $sql, $data);
            return $stmt;
    } catch(PDOException $e) {
        echo 'DB接続エラー: ' . $e->getMessage();
    }
}

//メッセージ詳細取得
function getPost($id){
    try {
        $dbh = dbConnect();
        $sql = 'SELECT * FROM posts WHERE id = :id';
        $data = array(':id' => $id);
        $stmt = queryPost($dbh, $sql, $data);
        return $stmt->fetch();
            
    } catch(PDOException $e) {
        echo 'DB接続エラー: ' . $e->getMessage();
    }
}
//メッセージ投稿
function createPost($message,$image,$user_id,$re_message_id,$re_message_user_id){
    try {
        $dbh = dbConnect();
        $sql = 'INSERT INTO posts (message,picture,user_id,re_message_id,re_message_user_id) VALUES (:message,:picture,:user_id,:re_message_id,:re_message_user_id)';
        $data = array(':message' => $message,':picture' => $image,':user_id' => $user_id,':re_message_id' => $re_message_id,':re_message_user_id' => $re_message_user_id);

        $stmt = queryPost($dbh, $sql, $data);
        if($stmt){
            return $stmt;
        }else{
            global $error;
            $error['message'] = MSG05;
        }
    } catch(PDOException $e) {
        echo 'DB接続エラー: ' . $e->getMessage();
    }
}

//メッセージ削除
function deletePost($id){
    try {
        $dbh = dbConnect();
        $sql = 'DELETE FROM posts WHERE id=:id';
        $data = array(':id' => $id);
        $stmt = queryPost($dbh, $sql, $data);
        return $stmt;    
    } catch(PDOException $e) {
        echo 'DB接続エラー: ' . $e->getMessage();
    }
}
//検索結果取得
function getSearch($key){
    try {
        $dbh = dbConnect();
        $sql = 'SELECT u.name, u.user_img, p.*, COUNT(f.user_id)  AS good FROM users u RIGHT JOIN posts p 
        ON u.id=p.user_id LEFT JOIN favorite f ON p.id = f.post_id WHERE message LIKE ? GROUP BY p.id ORDER BY p.created DESC';
        $data = array($key);
        $stmt = queryPost($dbh, $sql, $data);
        return $stmt;
            
    } catch(PDOException $e) {
        echo 'DB接続エラー: ' . $e->getMessage();
    }
}

//メッセージ詳細取得
function getMessage($re_id){
    try {
        $dbh = dbConnect();
        $sql = 'SELECT u.id, u.name, u.user_img, p.*, COUNT(f.user_id)  AS good FROM users u RIGHT JOIN posts p 
        ON u.id=p.user_id LEFT JOIN favorite f ON p.id = f.post_id WHERE p.id = ? GROUP BY p.id ORDER BY p.created DESC';
        $data = array($re_id);
        $stmt = queryPost($dbh, $sql, $data);

        if($stmt){
            return $stmt->fetch();
        }else{
            return false;
        }
    } catch(PDOException $e) {
        echo 'DB接続エラー: ' . $e->getMessage();
    }
}

//返信メッセージ取得
function getReMessage($re_id){
    try {
        $dbh = dbConnect();
        $sql = 'SELECT u.id, u.name, u.user_img, p.*, COUNT(f.user_id)  AS good FROM users u RIGHT JOIN posts p 
        ON u.id=p.user_id LEFT JOIN favorite f ON p.id = f.post_id WHERE p.re_message_id = ? GROUP BY p.id ORDER BY p.created DESC';
        $data = array($re_id);
        $stmt = queryPost($dbh, $sql, $data);
        return $stmt;
            
    } catch(PDOException $e) {
        echo 'DB接続エラー: ' . $e->getMessage();
    }
}

//フォローする
function follow($user_id,$re_id){
    try {
        $dbh = dbConnect();
        $sql = 'INSERT INTO follow SET user_id=?, follow=?';
        $data = array($user_id,$re_id);
        $stmt = queryPost($dbh, $sql, $data);
            return $stmt;
    } catch(PDOException $e) {
        echo 'DB接続エラー: ' . $e->getMessage();
    }
}

//フォローを外す
function followDelete($user_id,$re_id){
    try {
        $dbh = dbConnect();
        $sql = 'DELETE FROM follow WHERE user_id=? AND follow=?';
        $data = array($user_id,$re_id);
        $stmt = queryPost($dbh, $sql, $data);
            return $stmt;
    } catch(PDOException $e) {
        echo 'DB接続エラー: ' . $e->getMessage();
    }
}

//いいねする
function favorite($re_id,$user_id){
    try {
        $dbh = dbConnect();
        $sql = 'INSERT INTO favorite SET post_id=?, user_id=?';
        $data = array($re_id,$user_id);
        $stmt = queryPost($dbh, $sql, $data);
            return $stmt;
    } catch(PDOException $e) {
        echo 'DB接続エラー: ' . $e->getMessage();
    }
}

//いいねをはずす
function favoriteDelete($re_id,$user_id){
    try {
        $dbh = dbConnect();
        $sql = 'DELETE FROM favorite WHERE post_id=? AND user_id=?';
        $data = array($re_id,$user_id);
        $stmt = queryPost($dbh, $sql, $data);
            return $stmt;
    } catch(PDOException $e) {
        echo 'DB接続エラー: ' . $e->getMessage();
    }
}

//ユーザー画像を変更する際に元画像を削除
function user_img_delete($user_id){
    try {
        $dbh = dbConnect();
        $sql = 'SELECT user_img FROM users WHERE id=?';
        $data = array($user_id);
        $stmt = queryPost($dbh, $sql, $data);
        $result = $stmt->fetch();
        if($result['user_img'] !== "DefaultIcon.jpeg"){
            unlink('user_img/'.$result['user_img']);
        }
    } catch(PDOException $e) {
        echo 'DB接続エラー: ' . $e->getMessage();
    }
}

//ユーザーネームと画像を変更
function updateAll($name,$image,$user_id){
    try {
        $dbh = dbConnect();
        $sql = 'UPDATE users SET name=?, user_img=? WHERE id=?';
        $data = array($name,$image,$user_id);
        $stmt = queryPost($dbh, $sql, $data);
            return $stmt;
    } catch(PDOException $e) {
        echo 'DB接続エラー: ' . $e->getMessage();
    }
}

//ユーザーネームを変更
function update($name,$user_id){
    try {
        $dbh = dbConnect();
        $sql = 'UPDATE users SET name=? WHERE id=?';
        $data = array($name,$user_id);
        $stmt = queryPost($dbh, $sql, $data);
            return $stmt;
    } catch(PDOException $e) {
        echo 'DB接続エラー: ' . $e->getMessage();
    }
}

//いいねしたコメント一覧を取得
function getFavoriteComment($re_id){
    try {
        $dbh = dbConnect();
        $sql = 'SELECT u.name, u.user_img, p.*, COUNT(f.user_id) AS good FROM users u 
        RIGHT JOIN posts p ON u.id=p.user_id LEFT JOIN favorite f ON p.id = f.post_id 
        WHERE f.user_id = ? GROUP BY p.id ORDER BY p.created DESC';
        $data = array($re_id);
        $stmt = queryPost($dbh, $sql, $data);
        return $stmt;

    } catch(PDOException $e) {
        echo 'DB接続エラー: ' . $e->getMessage();
    }
}

//返信ツイート一覧を取得
function getAllReMessage($re_id){
    try {
        $dbh = dbConnect();
        $sql = 'SELECT u.id, u.name, u.user_img, p.*, COUNT(f.user_id) AS good FROM users u RIGHT JOIN posts p ON u.id=p.user_id LEFT JOIN favorite f 
        ON p.id = f.post_id WHERE p.re_message_user_id = ? GROUP BY p.id ORDER BY p.created DESC';
        $data = array($re_id);
        $stmt = queryPost($dbh, $sql, $data);
        return $stmt;

    } catch(PDOException $e) {
        echo 'DB接続エラー: ' . $e->getMessage();
    }
}

//パスワード一致チェック
function password_match($user_id,$pass){
    try {
        $dbh = dbConnect();
        $sql = 'SELECT password FROM users WHERE id = ?';
        $data = array($user_id);
        $stmt = queryPost($dbh, $sql, $data);
        if($stmt){
            $result = $stmt->fetch();
            if(password_verify($pass,$result['password'])){
                return true;
            }else{
                global $error;
                $error['pass'] = MSG08;
                return false;
            }
        }
    } catch(PDOException $e) {
        echo 'DB接続エラー: ' . $e->getMessage();
    }
}

//退会処理(登録情報)
function cancel_the_membership_user($user_id){
    try {
        $dbh = dbConnect();
        $sql = 'DELETE FROM users WHERE id=:id';
        $data = array(':id' => $user_id);
        $stmt = queryPost($dbh, $sql, $data);

    } catch(PDOException $e) {
        echo 'DB接続エラー: ' . $e->getMessage();
    }
}

//退会処理(ツイート情報)
function cancel_the_membership_post($user_id){
    try {
        $dbh = dbConnect();
        $sql = 'DELETE FROM posts WHERE user_id=:id';
        $data = array(':id' => $user_id);
        $stmt = queryPost($dbh, $sql, $data);

    } catch(PDOException $e) {
        echo 'DB接続エラー: ' . $e->getMessage();
    }
}

//退会処理(いいね情報)
function cancel_the_membership_good($user_id){
    try {
        $dbh = dbConnect();
        $sql = 'DELETE FROM favorite WHERE user_id=:id';
        $data = array(':id' => $user_id);
        $stmt = queryPost($dbh, $sql, $data);

    } catch(PDOException $e) {
        echo 'DB接続エラー: ' . $e->getMessage();
    }
}

//退会処理(フォロー情報)
function cancel_the_membership_follow($user_id){
    try {
        $dbh = dbConnect();
        $sql = 'DELETE FROM follow WHERE user_id=:id OR follow=:id';
        $data = array(':id' => $user_id);
        $stmt = queryPost($dbh, $sql, $data);

    } catch(PDOException $e) {
        echo 'DB接続エラー: ' . $e->getMessage();
    }
}

//退会処理(画像削除)
function cancel_the_membership_picture($user_id){
    try {
        $dbh = dbConnect();
        $sql = 'SELECT picture FROM posts WHERE user_id = :id';
        $data = array(':id' => $user_id);
        $stmt = queryPost($dbh, $sql, $data);

        while($result = $stmt->fetch()){
            $pictures[] = $result['picture'];
        }
        return $pictures;

    } catch(PDOException $e) {
        echo 'DB接続エラー: ' . $e->getMessage();
    }
}
?>