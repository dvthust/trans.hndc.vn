<?php

require __DIR__ . '/../../vendor/autoload.php';
use Stichoza\GoogleTranslate\GoogleTranslate;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;


//Config log format
$dateFormat = "Y-m-d H:i:s";
$output     = "[%datetime%] %channel%.%level_name%: %message%\n";
$formatter  = new LineFormatter($output, $dateFormat);

$logger = new Logger('translate');
$streamHandler = new StreamHandler(__DIR__ . '/../../logs/hndc-' . date('Y-m-d') . '.log',  Logger::DEBUG);

$streamHandler->setFormatter($formatter);
$logger->pushHandler($streamHandler);

$urls = array(
    'https://translate.google.com/translate_a/single',
    'https://translate.google.com.vn/translate_a/single',
    'https://translate.google.co.uk/translate_a/single',
    'https://translate.google.cn/translate_a/single'
);

//Keyword need to translate
$content = '';
$method  = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET' && isset($_GET['content'])){
    $content = $_GET['content'];
} elseif ($method == 'POST' && isset($_POST['content'])) {
    $content = $_POST['content'];
}

// Translates to 'en' from auto-detected language by default
$tr = new GoogleTranslate();
$url= $urls[array_rand($urls)];
$tr->setUrl($url);

// Detect language automatically
$tr->setSource();

$objTrans = new stdClass();

try {
    $logger->info('Content: ' . $content);

    //Using tor proxy
    $tr->setOptions(['proxy' => 'socks5://localhost:9050']);

    if ($content) {
        $tr->translate($content);

        $langDetect= $tr->getLastDetectedSource();
        switch ($langDetect) {
            case 'vi':
                $tr->setTarget('zh');
                break;
            default:
                $tr->setTarget('vi');
                break;
        }

        $text = $tr->translate($content);
        $objTrans->content = $text;
    } else {
        $objTrans->content = '';
    }
} catch (ErrorException $e) {
    $logger->error($e->getMessage());
    $objTrans->content = $content;
}

$logger->info('Translate: ' . $objTrans->content);
echo json_encode($objTrans);