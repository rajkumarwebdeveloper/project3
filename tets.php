<?php
session_start();
$filename=date('Y-m-d').".txt";
$myfile=fopen($filename,"a");
$out= array();
$path = str_replace('\\', '/', __DIR__."/test");
$reponame   =   'project';
$log="\n*******************START:LOG ENTRY ".date('Y-m-d h:i:s')."***************************\n";
$log.="Event:".$_SERVER['HTTP_X_GITHUB_EVENT']." Payload:\n";
$jwt='5f349e74d2783514ea327e387341c4b07513f83b';
$soanrqubeprojectid='AXRJX0nV5OERnSIoLwWg';
/***********************  Fetch data From Webhook and clone that repository to Dev server*************************************/
if(isset($_SERVER['CONTENT_TYPE'])){
    switch ($_SERVER['CONTENT_TYPE']) {
        case 'application/json':
            $json = $rawPost ?: file_get_contents('php://input');
            break;
        case 'application/x-www-form-urlencoded':
            $json = $_POST['payload'];
            break;
        default:
            die;
    }
}
$payload = json_decode($json);
if($_SERVER['HTTP_X_GITHUB_EVENT']=='pull_request'){
    $_SESSION['branch']= $branch= $payload->pull_request->head->ref;
    $_SESSION['repourl']= $repourl=$payload->pull_request->head->repo->clone_url;
    $_SESSION['check_runs_url']=$check_runs_url=$payload->pull_request->base->repo->url.'/check-runs';
    $_SESSION['checkrunname']=$check_runsarray['name']='Auto-Sonar-Qube-PR-Check';
    $_SESSION['sha']=$check_runsarray['head_sha']=$payload->pull_request->head->sha;
    $_SESSION['comments_url']= $comments_url=$payload->pull_request->url.'/comments';
    $cmd="cd ".$path." && git clone --branch ".$branch." ".$repourl;
    $reponame=basename($repourl, ".git").PHP_EOL;
    exec($cmd);
    $log.=$cmd." -Executed\n";
}else if($_SERVER['HTTP_X_GITHUB_EVENT']=='check_suite'){
    $_SESSION['branch']= $branch= $payload->check_suite->head_branch;
    $_SESSION['repourl']= $repourl=$payload->repository->clone_url;
    $_SESSION['check_runs_url']= $check_runs_url=$payload->check_suite->check_runs_url;
    $_SESSION['comments_url']= $comments_url=$payload->check_suite->pull_requests[0]->url;
    $_SESSION['checkrunname']=$check_runsarray['name']='Auto-Sonar-Qube-PR-Check';
    $_SESSION['sha']= $check_runsarray['head_sha']=$payload->check_suite->head_sha;
    $cmd="cd ".$path." && git clone --branch ".$branch." ".$repourl;
    $reponame=basename($repourl, ".git").PHP_EOL;
    exec($cmd);
    $log.=$cmd." -Executed\n";
}else{
    die;
}
/***********************  Fetch data From Webhook and clone that repository to Dev server*************************************/

$log.="branch: -".$branch. $_SESSION['branch']."\n";
$log.="repourl: -".$_SESSION['repourl']."\n";
$log.="comments_url: -".$_SESSION['comments_url']."\n";
$log.="check_runs_url: -".$_SESSION['check_runs_url']."\n";
$log.="head_sha: -".$_SESSION['sha']."\n";
/*********************** Create Checks  Comment *************************************/
$cmd ='cd '.$path.'/'.$reponame .' && sonar-scanner -Dsonar.projectKey=sonarqubeprcheck -Dsonar.sources=. -Dsonar.host.url=http://198.anglerfox.bid:9000 -Dsonar.login=admin -Dsonar.password=admin ';
exec($cmd);
$log.=$cmd." -Executed\n";
/*********************** Sonar Analysis *************************************/
/*********************** GET Sonar Result *************************************/
$analysis=CallAPI('GET', 'http://198.anglerfox.bid:9000/api/ce/task?id='.$soanrqubeprojectid, false,'Accept: application/json','');
$analysis_result=json_decode($analysis,true);
if($analysis_result['task']['status']=='SUCCESS'){
    $analysis_result_comment='AutoSonarQubePRChecks Quality Gate passed!';
}else{
    $analysis_result_comment='AutoSonarQubePRChecks Quality Gate failed!';
}
$log.="Anlysis Result: -".$analysis_result_comment."";
//echo '====================>'.$analysis_result_comment;print_R($analysis_result);
/*********************** GET Sonar Result *************************************/
/*********************** Review Comment *************************************/
$log.="------------------------check Runs--------------------------------\n";
$log.=checkruns($jwt);
$log.="-------------------------check Runs-------------------------------\n";
$log.="-------------------------Review Comments-------------------------------\n";
$log.=reviewcomments($analysis_result_comment,'',$jwt);
$log.="--------------------------Review Comments------------------------------\n";
/*********************** Review Comment *************************************/
$log.="*******************END:LOG ENTRY***************************\n";
fwrite($myfile,$log);
fclose($myfile);
print_R($payload);
return print_R($payload);


/*********************** Create Checks  Comment *************************************/
function checkruns($jwt){
    // $filename=date('Y-m-d').".txt";
    // $myfile=fopen($filename,"a+");
    if(isset($_SESSION['check_runs_url']) && $_SESSION['check_runs_url']!=''){
        $check_runsarray=array();
        $check_runsarray['name']=$_SESSION['checkrunname'];
        $check_runsarray['head_sha']=$_SESSION['sha'];
        $result=CallAPI('POST', $_SESSION['check_runs_url'], $check_runsarray,'Accept: application/vnd.github.v3.full+json','Authorization: token '.$jwt);
        return $result;
    }
   
    // fwrite($myfile,$log);
    // fclose($myfile);
}

/*********************** Create Checks  Comment *************************************/
/*********************** Review Comment *************************************/
function reviewcomments($comment,$path,$jwt){
    // $filename=date('Y-m-d').".txt";
    // $myfile=fopen($filename,"a+");
   // $log="/n Review Comments:".$comment;
   // $_SESSION['comments_url']='https://api.github.com/repos/rajkumarwebdeveloper/project/pulls/10/comments';
   // $_SESSION['sha']='bd572a21e2a6d067a372a3577eb07847e183ac97';
    if(isset($_SESSION['comments_url']) && $_SESSION['comments_url']!=''){
        $comments['body']=$comment;
        $comments['path']='';
       // $comments['line']=1;
       // $comments['side']='RIGHT';
        $comments['position']=0;
        $comments['commit_id']=$_SESSION['sha'];
       // $result=CallAPI('POST', $url, $comments,'Accept: application/vnd.github.v3+json','Authorization: bearer '.$token);
        $result=CallAPI('POST', $_SESSION['comments_url'], json_encode($comments),'Accept: application/vnd.github.antiope-preview+json','Authorization: token '.$jwt);
       // echo 'Commented API CALL ==================>'.$jwt;echo'<pre>';print_R($result);die;
      //  $log.=$result;
        return $result;
    }
    
    // fwrite($myfile,$log);
    // fclose($myfile);

}
/*********************** Review Comment *************************************/
/*********************** API *************************************/
function CallAPI($method, $url, $data = false,$header,$token='')
{
    $curl = curl_init();
    switch ($method)
    {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);

            if ($data){
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_PUT, 1);
            break;
        default:
            if ($data){
                $url = sprintf("%s?%s", $url, http_build_query($data));
            }
    }

    // Optional Authentication:
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
   // curl_setopt($curl, CURLOPT_HEADER, 1);
    curl_setopt($curl, CURLINFO_HEADER_OUT, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        $header,
        'User-Agent:autosonarqubeprchecks',
        $token
    ));
   
    $result = curl_exec($curl);
    curl_close($curl);

    return $result;
}
/*********************** API *************************************/



// use \Firebase\JWT\JWT;
// require_once  __DIR__.'/firebase/php-jwt/src/JWT.php';
// $privateKey = <<<EOD
// -----BEGIN RSA PRIVATE KEY-----
// MIIEpgIBAAKCAQEAzuKIpqfFWgD+pM4psFcRuaYVxqrYkjHVoSAhVr1chQsXBSUM
// e2oL8cnVbvPTXBzrwneXxqv1c73+VQCLm+mlyfUem9ndp3nk1jnvycAQ+rWsUtTd
// b5bRmSmAu1HY9bWP+r2CsDBZss0dexWhuXh9R7PIYq37PMLJX5uChzEM30ssi414
// VBIffvhHPGrumk/h9D4ugbjvO9nuqmCkfet4X2WNNmOAT79MZ9y4n+7+BLmrMfjB
// Oshf+SiqnAbj6v0ZqtfuxjEnvb/68P0tNpY9l/e3FwsMeM/Q1s1VllbEnmp2Wi4d
// Kej1gw5IKXwKvGMmfdO0yRuu/dTRqB2pv/E+gwIDAQABAoIBAQCthjJp3kRYlob/
// QnCTGKSkW1redIQMM0Jkz+dGsrN8X+3iAc4zYaI9HjYnxtkb5KIWTWr/V1Ibz1sY
// fsmab2IAP9l5jUYt5755tMScKr3TGzg7jZFhvFV3KulvtFnO1Ye7HuMT1qoVn5c/
// msg4IYiq8G993UHiF9sBlxTnQcvzKL7t6glp05E8bw4C6FLitXxPo2Oirh7n9j2n
// w5pdXkFSXH+QIZJGDDClbUHoOaFy0hXtDnOf+nw/4YLn9b9Jefm0rH0L0xgGYzqu
// qW5x5FsSt2bzvAKRnIcUPVGo71jD1eeAmG5KG2HTgYS9ePFTcPxckhm9CCpWNVd6
// xcVCkw5BAoGBAPEQyBBoHOe2xyRvE8K4n/dKJNOooVDcQTAZ6j/asZqaQkPItjuW
// XTfijRacApH/FI+fSEe67rnh7sFmzxQijnObfbHK8+fZGH7d1mbAw/pg+F3Ai2Js
// IjE4QJHuZPUxpX10LPk3T24XEnH+X12iSAcvMktHT7TggDxIw31jzRAjAoGBANuz
// qJq+/QOCibaXynJYLFU3HDjwV4yvYyRjfwzZnnB30zKkgn2OsE2s4iyA2+LITrpJ
// 6PVQHRoo4MThN9780JoidI6jhoRSylyk4hOqUWFT6C5cYrH7gbxcHQxthAOitKW1
// sij1/w9uKpWJRt3ODFnFSwZcIntzpw3aWgKXws4hAoGBAJ4/k/YhQohiFky2llRH
// esuNYquHkY5RaIG5IWuVlu2UwldZFTf8t2kOUew7sfxBZS/7MinUbw6bYG6ZnOrs
// 3HLL6jGit4bFnyz5V9vQQ1bD/Ycd9OJBdhi1gr3Jr4C+fJLkhvl686ujfbpTcCs5
// cus0cmG0iICGt+fbJGnV8DHdAoGBAMCDrkNjBewUb737Rl9p2fcV4noWSHEzomlZ
// chP9gTNGHF+s/dctuFloG4wpogQXx7y/VQ3YlJe+qC58t2uDFvtpI791lULQFRiX
// Nq9KuCLT1okBVU5md6lpAd9I+7v/z9HA5Au2ezi3LUN5Vgq4KeRj2DkLdP++OO0P
// n33UI9RhAoGBAI0EGxsd99cCHOkgf3DlBZalbFU69T4MacrZfQv2UgA0j0iPJGjl
// V+/xUilmH6gzAE5sxMf08EiueBpFPzeG1PwSmtFYckwfapnsQfVe8IJSyeVdvc8X
// u1LQBOeq2R8QN2EO+DFxhUEvcbyfODriLDRjpXnovmH6AT7GQe9F5yQV
// -----END RSA PRIVATE KEY-----
// EOD;
// $payload = array(
//     "iss" =>78280,
//     "aud" => "198.anglerfox.bid",
//     "iat" => time(),
//     "exp" => (time()+ 600)
// );
// $jwt = JWT::encode($payload, $privateKey, 'RS256');
//echo $jwt;
//$result=CallAPI('GET', 'https://api.github.com/repos/rajkumarwebdeveloper/project/pulls/comments', false,'Accept: application/json','Authorization: token '.$jwt);
//print_r($result);die;
//echo "Encode:\n" . print_r($jwt, true) . "\n";





// if(isset($_SESSION['token']) && $_SESSION['token']!=''){
//     $token= $_SESSION['token'];
//     checkruns();   
//     $log.='Session Token:-===>'.$token;
// }else{
//     /*********************** App Authenticating *************************************/
//     if((isset($_SESSION['code']) && $_SESSION['code']!='') || (isset($_GET['code']) && $_GET['code']!='')){
//         echo 'second  call==============================>';
//         $data=array();
//         $data['code']=$_GET['code']??$_SESSION['code'];
//         $log.='Code:-'.$_GET['code'];
//         $data['client_id']='Iv1.b715967149cde014';
//         $data['client_secret']='6b44f5cebcae65e60347259c779d0a578f617fbe';
//         $result=CallAPI('POST', 'https://github.com/login/oauth/access_token', $data,'Accept: application/json');
//         $results=json_decode($result);
//         $_SESSION['token']=$token=$results->access_token;
//         checkruns();   
//         $log.='Token:-===>'.$token;
//     }else{
//         echo 'First call==============================>';
//         $result=CallAPI('GET', 'https://github.com/login/oauth/authorize?client_id=Iv1.b715967149cde014', false,'Accept: application/json');
//         $log.='First result:-===>'.$result;
//         fwrite($myfile,$log);
//         fclose($myfile);
//         die;
//         //header('Location:https://github.com/login/oauth/authorize?client_id=Iv1.b715967149cde014');
//     }
// }
?>

