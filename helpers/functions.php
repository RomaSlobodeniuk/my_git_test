<?php

session_start();
require_once './config/config.php';
checkSession();
checkLoginOrRegister();

function getParams()
{
    if (empty($_REQUEST)) {
        return false;
    }

    return $_REQUEST;
}

function checkLoginOrRegister()
{
    $params = getParams();
    if (empty($params['form_type'])) {
        return;
    }

    if (!empty($params['form_type']) && $params['form_type'] === 'register') {
        processRegister($params);
    }

    if (!empty($params['form_type']) && $params['form_type'] === 'login') {
        processLogin($params);
    }
}

function processLogin($params)
{
    if (empty($params['password'])) {
        return;
    }

    $usersSource = file_get_contents('source/users.json');
    $users = json_decode($usersSource, true);
    $passwordHash = md5($params['password']);
    if (!array_key_exists($params['email'], $users)) {
        $message = [
            'type' => 'danger',
            'message' => 'There is no user with email: "' . $params['email'] . '". Please register.'
        ];
        header('Location: ' . ROOT_PATH . 'index.php?page=login');
    } elseif ($users[$params['email']]['password'] !== $passwordHash) {
        $message = [
            'type' => 'danger',
            'message' => 'You have typed the wrong password, please try again.'
        ];
        header('Location: ' . ROOT_PATH . 'index.php?page=login');
    } else {
        $_SESSION['email'] = $params['email'];
        $_SESSION['time'] = time();
        $sessionDuration = !empty($params['remember_me']) ? LONG_SESSION_TIME : ALLOWED_SESSION_TIME;
        $expirationTime = $_SESSION['time'] + $sessionDuration;
        setcookie(
            "login_timestamp",
            $_SESSION['time'],
            $expirationTime,
            ROOT_PATH,
            "localhost"
        );
        setcookie(
            "allowed_session_time",
            $sessionDuration,
            $expirationTime,
            ROOT_PATH,
            "localhost"
        );
        $message = [
            'type' => 'success',
            'message' => 'You have logged in successfully! Your email: "' . $params['email'] . '".'
        ];
        header('Location: ' . ROOT_PATH);
    }

    pushMessage($message);
    die();
}

function processRegister($params)
{
    if ($params['pass_1'] !== $params['pass_2']) {
        $message = [
            'type' => 'danger',
            'message' => 'Sorry, your passwords did not match! Please enter passwords and try again.'
        ];
        pushMessage($message);
        header('Location: ' . ROOT_PATH . 'index.php?page=login');
    } elseif ($params['pass_1'] === $params['pass_2']) {
        $sourceFile = './source/users.json';
        if (file_exists($sourceFile)) {
            $usersSource = file_get_contents($sourceFile);
            $users = json_decode($usersSource, true);
            if (array_key_exists($params['email'], $users)) {
                $message = [
                    'type' => 'danger',
                    'message' => 'The user with email: "' . $params['email'] . '" is already exists!'
                ];
                pushMessage($message);
                header('Location: ' . ROOT_PATH . 'index.php?page=login');
                die();
            }
        }

        $passwordHash = md5($params['pass_1']);
        $users[$params['email']]['password'] = $passwordHash;
        if (!empty($_FILES['file'])) {
            $users[$params['email']]['logo'] = processFile($passwordHash);
        }

        $usersSource = json_encode($users);
        file_put_contents($sourceFile, $usersSource, LOCK_EX);
        chmod($sourceFile, 0777);

        $_SESSION['email'] = $params['email'];
        $_SESSION['time'] = time();
        $expirationTime = $_SESSION['time'] + ALLOWED_SESSION_TIME;
        setcookie(
            "login_timestamp",
            $_SESSION['time'],
            $expirationTime,
            ROOT_PATH,
            "localhost"
        );
        setcookie(
            "allowed_session_time",
            ALLOWED_SESSION_TIME,
            $expirationTime,
            ROOT_PATH,
            "localhost"
        );
        $message = [
            'type' => 'success',
            'message' => 'You have been registered successfully! Your email: "' . $params['email'] . '"'
        ];
        pushMessage($message);
        header('Location: ' . ROOT_PATH);
    }

    die();
}

function processFile($passwordHash)
{
    $uploads_dir = './assets/images/logos/';
    if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $message = [
            'type' => 'info',
            'message' => 'Your file was not uploaded for some reasons.'
        ];
        pushMessage($message);
        return '';
    } else {
        $tmp_name = $_FILES["file"]["tmp_name"];
        $extension = '.' . explode('/', $_FILES["file"]["type"])[1];
        $destination = $uploads_dir . $passwordHash . $extension;
        if (move_uploaded_file($tmp_name, $destination)) {
            chmod($destination, 0777);
        }

        return $destination;
    }
}

function checkSession()
{
    if (empty($_SESSION['email'])) {
        return;
    }

    $currentTimestamp = time();
    $sessionDuration = !empty($_COOKIE['allowed_session_time']) ? (int)$_COOKIE['allowed_session_time'] : ALLOWED_SESSION_TIME;
    $timeToLogout = $sessionDuration - ($currentTimestamp - $_SESSION['time']);
    $message = [
        'type' => 'info',
        'message' => 'You have "' . $timeToLogout . '" seconds to logout!'
    ];
    pushMessage($message);

    if ($currentTimestamp - $_SESSION['time'] > ALLOWED_SESSION_TIME) {
        logout();
    }
}

function pushMessage($message) {
    $_SESSION['messages'][] = $message;
}

function logout()
{
    unset($_SESSION['email']);
    unset($_SESSION['time']);
    unset($_SESSION['messages']);
    setcookie(
        'login_timestamp',
        '',
        time() - 3600,
        ROOT_PATH,
        "localhost"
    );
    setcookie(
        'allowed_session_time',
        '',
        time() - 3600,
        ROOT_PATH,
        "localhost"
    );
    header('Location: ' . ROOT_PATH);
    $message = [
        'type' => 'success',
        'message' => 'You have been logout successfully!'
    ];
    pushMessage($message);
    die();
}

function getSourceData($fileName)
{
    $sourceContent = getSourceContent($fileName);
    $sourceData = json_decode($sourceContent, true);
    return $sourceData;
}

function getSourceContent($fileName)
{
    $sourceContent = file_get_contents($fileName);
    return $sourceContent;
}

function getHeader($data, $page)
{
    $fileName = './templates/header/header.html';
    $header = getSourceContent($fileName);
    $header = str_replace('{{title}}', $data['title'], $header);
    $header = str_replace('{{base_path}}', ROOT_PATH, $header);
    $navigation = getNavigation($page);
    $header = str_replace('{{navigation}}', $navigation, $header);
    $messages = getMessages();
    $header = str_replace('{{messages}}', $messages, $header);
    return $header;
}

function getMessages()
{
    if (empty($_SESSION['messages'])) {
        return '';
    }

    $fileName = './templates/header/messages.html';
    $messageTemplate = getSourceContent($fileName);
    $messagesHtml = '<div class="jumbotron messages-container">';
    foreach ($_SESSION['messages'] as $message) {
        $tmpTemplate = $messageTemplate;
        $tmpTemplate = str_replace('{{type}}', $message['type'], $tmpTemplate);
        $tmpTemplate = str_replace('{{message}}', $message['message'], $tmpTemplate);
        $messagesHtml .= $tmpTemplate;
    }

    $messagesHtml .= '</div>';
    unset($_SESSION['messages']);
    return $messagesHtml;
}

function getNavigation($page)
{
    $navigationFileName = './source/navigation.json';
    $navigationData = getSourceData($navigationFileName);
    if (empty($navigationData)) {
        return '';
    }

    $navigationTemplateName = './templates/header/navigation.html';
    $navigationTemplate = getSourceContent($navigationTemplateName);
    $navigationTemplate = str_replace('{{logo_title}}', $navigationData['logo_title'], $navigationTemplate);
    $userEmail = !empty($_SESSION['email']) ? $_SESSION['email'] : '';
    $navigationTemplate = str_replace('{{user_email}}', $userEmail, $navigationTemplate);
    if (!empty($_SESSION['email'])) {
        $fileName = './source/users.json';
        $usersSource = getSourceContent($fileName);
        $users = json_decode($usersSource, true);
        $logoSrc = $users[$_SESSION['email']]['logo'];
        $userLogo = '<img src="' . $logoSrc . '" width="30" height="30" alt="user logo">';
        $navigationTemplate = str_replace('{{logo}}', $userLogo, $navigationTemplate);
    } else {
        $navigationTemplate = str_replace('{{logo}}', '', $navigationTemplate);
    }

    $linksFileName = './templates/header/links.html';
    $linksTemplateHtml = getSourceContent($linksFileName);
    $linksHtml = '';
    $links = $navigationData['links'];
    $isLoggedIn = empty($_SESSION['email']) ? false : true;
    foreach ($links as $key => $link) {
        if ($isLoggedIn && $key === 'link_3') {
            continue;
        }

        if (!$isLoggedIn && $key === 'link_4') {
            continue;
        }

        $linksTemplate = $linksTemplateHtml;
        $active = $page === strtolower($link['key']) ? 'active' : '';
        $additional = $page === strtolower($link['key']) ? '<span class="sr-only">(current)</span>' : '';
        $linksTemplate = str_replace('{{active}}', $active, $linksTemplate);
        $linksTemplate = str_replace('{{is_active_additional}}', $additional, $linksTemplate);
        $linksTemplate = str_replace('{{name}}', $link['name'], $linksTemplate);
        $linksTemplate = str_replace('{{href}}', ROOT_PATH . $link['href'], $linksTemplate);
        $linksHtml .= $linksTemplate;
    }

    $navigationTemplate = str_replace('{{links}}', $linksHtml, $navigationTemplate);
    return $navigationTemplate;
}

function getMainContent($data, $template)
{
    $mainFileName = "./templates/$template.html";
    $mainTemplate = getSourceContent($mainFileName);
    $mainTemplate = str_replace('{{greetings}}', $data['greetings'], $mainTemplate);
    $mainTemplate = str_replace('{{description}}', $data['description'], $mainTemplate);
    $mainTemplate = str_replace('{{additional}}', $data['additional'], $mainTemplate);
    $mainTemplate = str_replace('{{link}}', $data['link'], $mainTemplate);
    $mainTemplate = str_replace('{{link_name}}', $data['link_name'], $mainTemplate);
    $pageContent = $data['page_content'];
    if (empty($pageContent)) {
        return $mainTemplate;
    }

    if ($template === 'index') {
        $indicatorsHtml = '';
        $sliderHtml = '';
        $sliderFileName = './templates/index/slider.html';
        $sliderTemplateHtml = getSourceContent($sliderFileName);
        $i = 0;
        foreach ($pageContent['slider'] as $slider) {
            $sliderTemplate = $sliderTemplateHtml;
            $active = $i === 0 ? 'active' : '';
            $indicatorsHtml .= '<li data-target="#carouselExampleControls" data-slide-to="' . $i . '" class="' . $active .'"></li>';
            $sliderTemplate = str_replace('{{active}}', $active, $sliderTemplate);
            $sliderTemplate = str_replace('{{src}}', ROOT_PATH . IMAGES_PATH . $slider['src'], $sliderTemplate);
            $sliderTemplate = str_replace('{{alt}}', $slider['alt'], $sliderTemplate);
            $sliderHtml .= $sliderTemplate;
            $i++;
        }

        $mainTemplate = str_replace('{{indicators}}', $indicatorsHtml, $mainTemplate);
        $mainTemplate = str_replace('{{slider_content}}', $sliderHtml, $mainTemplate);
    }

    if ($template === 'articles') {
        $mainTemplate = str_replace('{{articles_title}}', $pageContent['title'], $mainTemplate);
        $mainTemplate = str_replace('{{articles_description}}', $pageContent['description'], $mainTemplate);
        $current = !empty($_GET['current']) ? $_GET['current'] : 1;
        $perPage = 3;

        $start = $current == 1 ? $current : ($current * $perPage) - ($perPage - 1) ;
        $end = $start + $perPage;

        $articlesHtml = '';
        $articleFileName = './templates/articles/article.html';
        $articleTemplateHtml = getSourceContent($articleFileName);
        $articles = $pageContent['articles'];
        for ($i = $start; $i < $end; $i++ ) {
            $articleKey = 'article_' . $i;
            if (empty($articles[$articleKey])) {
                continue;
            }

            $articleTemplate = $articleTemplateHtml;
            $articleTemplate = str_replace('{{src}}', ROOT_PATH . $articles[$articleKey]['src'], $articleTemplate);
            $articleTemplate = str_replace('{{name}}', $articles[$articleKey]['name'], $articleTemplate);
            $articleTemplate = str_replace('{{text}}', $articles[$articleKey]['text'], $articleTemplate);
            $articleTemplate = str_replace('{{date}}', $articles[$articleKey]['date'], $articleTemplate);
            $articlesHtml .= $articleTemplate;
        }

        $mainTemplate = str_replace('{{articles}}', $articlesHtml, $mainTemplate);

        $paginationHtml = '';
        $pageLinkFileName = './templates/header/page_link.html';
        $pageLinkTemplateHtml = getSourceContent($pageLinkFileName);
        $pagEnd = round(count($pageContent['articles'])/$perPage);
        for ($i = 1; $i < $pagEnd + 1; $i++) {
            $pageLinkTemplate = $pageLinkTemplateHtml;
            $active = $current == $i ? 'active' : '';
            $additional = $current == $i ? '<span class="sr-only">(current)</span>' : '';
            $pageLinkTemplate = str_replace('{{active}}', $active, $pageLinkTemplate);
            $pageLinkTemplate = str_replace('{{href}}', ROOT_PATH . 'articles/' . $i, $pageLinkTemplate);
            $pageLinkTemplate = str_replace('{{number}}', $i, $pageLinkTemplate);
            $pageLinkTemplate = str_replace('{{current}}', $additional, $pageLinkTemplate);
            $paginationHtml .= $pageLinkTemplate;
        }


        $mainTemplate = str_replace('{{pagination}}', $paginationHtml, $mainTemplate);
    }

    if ($template === 'login') {

    }

    return $mainTemplate;
}

function getFooter($data)
{
    $footerFileName = './templates/footer/footer.html';
    $footer = getSourceContent($footerFileName);
    $footer = str_replace('{{heading}}', $data['footer']['heading'], $footer);
    return $footer;
}