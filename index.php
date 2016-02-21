<?php
$error = null;
$uri = strtok($_SERVER['REQUEST_URI'], '?');
$parts = explode('/', $uri);
$code = array_pop($parts);
date_default_timezone_set("America/Sao_Paulo");
include("static.php");
include_once("lib/recaptchalib.php");

if ($code) {
    include_once("lib/Thrash.class.php");
    if ($thrash = Thrash::get_by_code($code)) {
        $title = "<strong>$site</strong> | {$thrash->title}";
    } else {
        die('codigo invalido');
    }
} else {
    $test = strpos($_SERVER['HTTP_HOST'], 'localhost') === 0;
	if (isset($_POST['url']) && ($test || isset($_POST["recaptcha_response_field"]))) {
                if (!$test) $resp = recaptcha_check_answer ($privatekey,
                                                $_SERVER["REMOTE_ADDR"],
                                                @$_POST["recaptcha_challenge_field"],
                                                @$_POST["recaptcha_response_field"]);

                if ($test || $resp->is_valid) {    
                    include_once("lib/Thrash.class.php");
                    $thrash = Thrash::create($_POST['url'], $_POST['title']);
                    $redirect_url = $thrash->get_url();
                    if ($thrash->blocked_domain) $redirect_url .= "?created=1";
                    header("Location: ".$redirect_url);
                    die();
                } else {
                        $error = $resp->error;
                }
        }
}
?>
<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <title><?php echo strip_tags($title); ?></title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="stylesheet" href="css/normalize.min.css">
        <link rel="stylesheet" href="css/main.css">

        <!--[if lt IE 9]>
            <script src="//html5shiv.googlecode.com/svn/trunk/html5.js"></script>
            <script>window.html5 || document.write('<script src="js/vendor/html5shiv.js"><\/script>')</script>
        <![endif]-->

        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
        <script src="js/main.js"></script>
    </head>
    <body>
        <!--[if lt IE 7]>
            <p class="browsehappy">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade your browser</a> to improve your experience.</p>
        <![endif]-->

        <div class="header-container">
            <header class="wrapper clearfix">
                <h1 class="title"><?php echo $title; ?></h1>
                <nav>
                    <ul>
                        <li><a href="./">encurtar url</a></li>
                        <li><a href="#reclameaqui" onclick="alert('¯\\_(ツ)_/¯');">reclamações</a></li>
                    </ul>
                </nav>
            </header>
        </div>

        <?php
        if (isset($thrash) && $thrash && $thrash->blocked_domain == 1) {
            $url = "http://webcache.googleusercontent.com/search?q=cache:".$thrash->original_url;
            if (isset($_REQUEST['created'])) {
                include("inc/share.php");
                ?>
                <p>A URL que voc&ecirc; encurtou est&aacute; num dom&iacute;nio que bloqueia o <?php echo $site; ?>. Esta url funcionar&aacute; como um redirecionamento para o cache do google.</p>
                <p><a href="<?php echo $url; ?>">Exemplo do redirecionamento</a>.</p>
                <?php
            } else {
                header("Location: $url");
                die();
            }
        } else if (isset($thrash) && $thrash) {
            $imgs = $thrash->get_image_path();
            ?>
            <p>URL original: <?php echo "<script>document.write(decode_base64('" . base64_encode("<a href=\"{$thrash->original_url}\">{$thrash->original_url}</a>") . "'));</script>"; ?> - Capturada em: <?php echo date("d/m/Y H\hi", strtotime($thrash->date_created)); ?></p>
            <meta property="og:image" content="<?php echo $imgs[0]; ?>"/>
            <meta property="og:title" content="<?php echo $title; ?>"/>
            <?php
            include("inc/share.php");
            foreach ($imgs as $img) {
                echo "<img src=\"${img}\" /><br/>";
            }
        } else {
            ?>
            <div id="mask"></div>
            <div class="main-container">
                <div class="main wrapper clearfix">
                    <article>
                    	<h1>Encurtar url</h1>
                    	<p>
                    		<form accept-charset=\"UTF-8\" method="post" class="<?php if (isset($_POST['url']) && isset($_POST['title'])): ?>scriptlet ready<?php endif; ?>">
                    			<input type="submit" value="<?php if (isset($_POST['url']) && isset($_POST['title'])): ?>gerar<?php else: ?>prosseguir<?php endif; ?>" />
                    			<fieldset>Endereço: <input type="text" name="url" value="<?php echo @$_REQUEST['url']; ?>" /></fieldset>
                                <?php if (@$_GET['error'] == 'load') : ?><span class="error">Não foi possível carregar a página. Por favor, confira o endereço e tente novamente.</span><?php endif; ?>
                                <fieldset class="title <?php if (isset($_POST['title'])): ?>filled<?php endif; ?>"><label>Título:</label><textarea name="title"><?php echo @$_POST['title']; ?></textarea></fieldset>
                                <fieldset class="captcha <?php if ($error): ?>error<?php endif; ?>"> 
                                    <label>Erro no captcha. Tente novamente:</label><!-- <?php echo $error; ?> -->
                                    <?php
                                    if (!$test) echo recaptcha_get_html($publickey, $error);
                                    ?>
                                </fieldset>
                    		</form>
                    	</p>
                    </article>

                    <aside>
                        <h3>O que é?</h3>
                        <p>O <?php echo $site; ?> é um serviço de compartilhamento/encurtamento de URLs com propósito de denúncia/comentário crítico. Em vez da página original, a URL encurtada direciona para uma cópia (em imagem) do conteúdo, de modo que não se aumentará o tráfego ou o <em>pagerank</em> da página em questão. Além disso, a cópia ficará disponível mesmo que a página original seja tirada do ar.</p>
                        <p><strong>IMPORTANTE</strong><br/>
                        	NÃO compartilhe conteúdo ilegal e criminoso, tais como pedofilia ou exposição vexatória de pessoas, por este serviço. O conteúdo será retirado do ar e seu acesso ao serviço barrado. Eventualmente forneceremos seu IP a autoridades competentes.
                        	Há outras formas de denunciar e é criminosa (além de contraproducente) a reprodução desses conteúdos. Use, por exemplo: <a href="http://www.dpf.gov.br/servicos/fale-conosco/denuncias">http://www.dpf.gov.br/servicos/fale-conosco/denuncias</a>
                            <p>
                            Tampouco use o <?php echo $site; ?> como um encurtador comum: fazer cópia do conteúdo implica em custos de servidor, e um crescimento da demanda tornaria inviável o serviço, que jamais terá fins lucrativos. Por favor, use apenas para denúncia e compartilhamento de conteúdo desprezível.
                            </p>
                        </p>
                    </aside>
                </div> <!-- #main -->
            </div> <!-- #main-container -->
            <?php
        }
        ?> 

        <div class="footer-container">
            <footer class="wrapper">
                <a href="https://github.com/naofode/naofode.xyz">código fonte</a>
                &nbsp;&nbsp;-&nbsp;&nbsp;
                <a title="botao <?php echo $site; ?>" href='javascript:var d=document,b=d.body,div=d.createElement("div");div.innerHTML="<form accept-charset=\"UTF-8\" action=\"http://<?php echo $site; ?>\" method=\"post\" target=\"_blank\"><input name=\"url\"><input name=\"title\"></form>";div.style.display="none";b.appendChild(div);var f=div.children[0];f["url"].value=window.location.href;f["title"].value=d.title;f.submit();' onclick="return false;">botão <?php echo $site; ?></a> (arraste para sua barra de favoritos e clique quando estiver na página que deseja encurtar)
            </footer>
        </div>

        <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
        <script>window.jQuery || document.write('<script src="js/vendor/jquery-1.11.0.min.js"><\/script>')</script>

        <script src="js/plugins.js"></script>
        <script src="js/main.js"></script>

        <script>
            (function(b,o,i,l,e,r){b.GoogleAnalyticsObject=l;b[l]||(b[l]=
            function(){(b[l].q=b[l].q||[]).push(arguments)});b[l].l=+new Date;
            e=o.createElement(i);r=o.getElementsByTagName(i)[0];
            e.src='//www.google-analytics.com/analytics.js';
            r.parentNode.insertBefore(e,r)}(window,document,'script','ga'));
            ga('create', 'UA-50387468-1', 'naofo.de');ga('send','pageview');
        </script>
    </body>
</html>
