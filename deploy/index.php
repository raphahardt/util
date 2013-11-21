<?php
/**
 * Inspired in a answer in stackoverflow.com about this: http://stackoverflow.com/a/4611276
 */
 

/* create directory if doesn't exist */
function createDir($dirName, $perm = 0777) {
    $dirs = explode('/', $dirName);
    $dir='';
    foreach ($dirs as $part) {
        $dir.=$part.'/';
        if (!is_dir($dir) && strlen($dir)>0) {
            mkdir($dir, $perm);
        }
    }
}

function createDirFTP($ftp, $dirName) {
    $dirs = explode('/', $dirName);
    $dir='';
    foreach ($dirs as $part) {
        $dir.=$part.'/';
        if (!is_dir($dir) && strlen($dir)>0) {
            ftp_mkdir($ftp, $dir, $perm);
			//ftp_chmod($ftp, $perm, $dir);
        }
    }
}

/* deletes dir recursevely, be careful! */
function deleteDirRecursive($f) {

    if (strpos($f, "_deploy" . "/") === false) {
        exit("deleteDirRecursive() protection disabled deleting of tree: $f  - please edit the path check in source php file!");
    }

    if (is_dir($f)) {
        foreach(scandir($f) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            deleteDirRecursive($f . "/" . $item);
        }    
        rmdir($f);

    } elseif (is_file($f)) {
        unlink($f);
    }
}

$lastRepoDirFile = "last_repo_dir.txt";
$repo = isset($_POST['repo']) ? $_POST['repo'] : null;


if (!$repo && is_file($lastRepoDirFile)) {
    $repo = file_get_contents($lastRepoDirFile);
}

// pega as opcoes padrao
$ini = parse_ini_file("options.ini");

$range = isset($_POST['range']) ? $_POST['range'] : "HEAD~1 HEAD";
$ftp_host = isset($_POST['ftp_host']) ? $_POST['ftp_host'] : $ini['ftp_host'];
$ftp_port = isset($_POST['ftp_port']) ? $_POST['ftp_port'] : $ini['ftp_port'];
$ftp_user = isset($_POST['ftp_user']) ? $_POST['ftp_user'] : $ini['ftp_user'];
$ftp_pwd = isset($_POST['ftp_pwd']) ? $_POST['ftp_pwd'] : $ini['ftp_pwd'];
$ftp_folder = isset($_POST['ftp_folder']) ? $_POST['ftp_folder'] : $ini['ftp_folder'];

$exportDir = $ini['export_dir'];

$all_repositories = array();

$basedir = $ini['base_repo_dir'];
if (is_dir($basedir)) {
	foreach(scandir($basedir) as $item) {
		if ($item == '.' || $item == '..') {
			continue;
		}
		if (is_dir($basedir.'/'.$item)) {
			if (is_dir($basedir.'/'.$item.'/.git')) {
				$all_repositories[] = $item;
			}
		}
	}    

}


?>

<html>
<head>
<title>Git export changed files</title>
<meta charset='utf-8'>
<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.2/css/bootstrap.min.css">
<style>
	body {
		margin:40px;
	}
</style>
</head>

<body>
<div class="container">
	<div class="page-header">
		<h1>Git Repository FTP Deployer</h1>
	</div>
	<p class="lead">
		Deploy your website via FTP from your repository... only changed files from a commit range!
	</p>
	
	<form action="" method="post" class="form-horizontal">
		
		<div class="row">
			<div class="col-sm-offset-2 col-sm-10">
				<h3>Git</h3>
			</div>
		</div>
		
		<div class="form-group">
			<div class="col-sm-2 control-label"><label>Repository</label></div>
			<div class="col-sm-10 control">
				<div class="input-group">
					<div class="input-group-addon"><?=$ini['base_repo_dir'] ?>/</div>
					<select size=1 name="repo" class="form-control">
						<?php foreach ($all_repositories as $r): ?>
						<option<?=($repo==$r ? ' selected':'')?>><?=$r?></option>
						<?php endforeach; ?>
					</select>
				</div>
				
			</div>
		</div>
		<!--<input type="text" name="repo" value="<?=htmlspecialchars($repo) ?>" size="25">-->

		<div class="form-group">
			<div class="col-sm-2 control-label"><label>Range</label></div>
			<div class="col-sm-10 control">
				<input type="text" name="range" value="<?=htmlspecialchars($range) ?>" class="form-control">
				<div class="help-block">
					Tags/branchs separated by space
				</div>
			</div>
		</div>
		
		<div class="row">
			<div class="col-sm-offset-2 col-sm-10">
				<h3>FTP</h3>
			</div>
		</div>
		
		<div class="form-group">
			<div class="col-sm-2 control-label"><label>Host</label></div>
			<div class="col-sm-5 control">
				<input type="text" name="ftp_host" value="<?=htmlspecialchars($ftp_host) ?>" placeholder="ftp://ftp.example.com" class="form-control">
			</div>
			<div class="col-sm-1 control-label"><label>Port</label></div>
			<div class="col-sm-2 control">
				<input type="text" name="ftp_port" value="<?=htmlspecialchars($ftp_port) ?>" placeholder="21" class="form-control">
			</div>
		</div>
		
		<div class="form-group">
			<div class="col-sm-2 control-label"><label>Username</label></div>
			<div class="col-sm-4 control">
				<input type="text" name="ftp_user" value="<?=htmlspecialchars($ftp_user) ?>" placeholder="username" class="form-control">
			</div>
		</div>
		<div class="form-group">
			<div class="col-sm-2 control-label"><label>Password</label></div>
			<div class="col-sm-4 control">
				<input type="password" name="ftp_pwd" value="<?=htmlspecialchars($ftp_pwd) ?>" placeholder="****" class="form-control">
			</div>
		</div>
		
		<div class="form-group">
			<div class="col-sm-2 control-label"><label>Destination Local folder</label></div>
			<div class="col-sm-10 control">
				<input type="text" name="." readonly value="<?=htmlspecialchars($exportDir) ?>" placeholder="You should define in .ini" class="form-control">
				<div class="help-block">
					Temporary folder where changed files will be moved. To change the folder, edit the .ini. Recommended a empty folder.
				</div>
			</div>
		</div>
		
		<div class="form-group">
			<div class="col-sm-2 control-label"><label>Destination Remote folder</label></div>
			<div class="col-sm-10 control">
				<input type="text" name="ftp_folder" value="<?=htmlspecialchars($ftp_folder) ?>" placeholder="/public_html/" class="form-control">
			</div>
		</div>
		
		<div class="row">
			<div class="col-sm-offset-2 col-sm-10">
				<input type="submit" class="btn btn-lg btn-primary" value="Exportar">
			</div>
		</div>
	</form>
<br/>


<?php
if (!empty($_POST)) {

	/* ************************************************************** */
    file_put_contents($lastRepoDirFile, $repo); 

    $repoDir = $ini['base_repo_dir'] ."/$repo";
    $repoDir = rtrim($repoDir, '/\\');
	
	echo "<hr/>source repository: <strong>$repoDir</strong><br/>";
    echo "exporting to: <strong>$exportDir</strong><br/><br/>\n";


    createDir($exportDir);

    // empty export dir
    foreach (scandir($exportDir) as $file) {
        if ($file != '..' && $file != '.') {
            deleteDirRecursive("$exportDir/$file");
        }
    }

    // execute git diff
    $cmd = "git --git-dir=$repoDir/.git diff $range --name-only";

    exec("$cmd 2>&1", $output, $err);

    if ($err) {
        echo "Command error: <br/>";
        echo implode("<br/>", array_map('htmlspecialchars', $output));
        exit;
    }

	$ftp = ftp_connect($ftp_host, $ftp_port);
	
	if ($ftp) {

		if (ftp_login($ftp, $ftp_user, $ftp_pwd)) {
		
			ftp_chdir($ftp, $ftp_folder);
	
			// $output contains a list of filenames with paths of changed files
			foreach ($output as $file) {

				$source = "$repoDir/$file";

				if (is_file($source)) {
					if (strpos($file, '/')) {
						createDir("$exportDir/" .dirname($file));
						createDirFTP($ftp, "$exportDir/" .dirname($file));
					}

					copy($source, "$exportDir/$file");
					ftp_put($ftp, "$file", "$exportDir/$file", FTP_ASCII);
					echo "$file<br/>\n";

				} else {
					// deleted file
					echo "<span style='color: red'>$file</span><br/>\n";
				}
			}
		} else {
			echo 'Error logging in to FTP<br>';
		}	
		ftp_close($ftp);
		
	} else {
		echo 'Error connecting to FTP<br>';
	}
}
?>
</div>

</body>
</html>