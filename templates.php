<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Tom
 * Date: 5.3.13
 * Time: 17:13
 * To change this template use File | Settings | File Templates.
 */


namespace Skoumal;

class Template {
    private $template;

    public function __construct() {
        $this->template = new \Nette\Templating\Template;
        $this->template->registerHelperLoader('\Nette\Templating\Helpers::loader');
        $this->template->registerFilter(new \Nette\Latte\Engine);
        $this->template->directories = \FileSystem::directoryList('./../');
    }

    public function render() {
        $this->template->render();
    }

    public function homepage() {
        $this->template->setSource('<!doctype html>
<html lang="cs" dir="ltr">
<head>
<meta charset="UTF-8">
<title></title>
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
</head>
<body>
<table>
{foreach $directories as $directory}
<tr>
<td class="dir-name">{$directory}</td>
<td class="dir-open">Otevřít</td>
<td class="dir-download">Stáhnout v ZIPu</td>
<td class="dir-detail">Zobrazit velikost<div class="data"></div></td>
</tr>
{/foreach}
</table>

<script>

$(document).ready(function() {
    $(".dir-open").click(function() {
        var directory = $(this).prev(".dir-name").text();
    });

    $(".dir-download").click(function() {

    });

    $(".dir-detail").click(function() {
        var directory = $(this).parent().children(".dir-name").text();
        //alert(directory);
        var elem = $(this).parent().children(".dir-detail").children(".data");
        $.getJSON("index.php?action=detail&directory="+directory, function(data) {

            elem.append("Velikost: "+data["size"]+" MiB<br>");
            elem.append("Počet souborů: "+data["countOfObjects"]);
          /*$.each(data, function(key, val) {

            $(".dir-detail .data").append(val);
          });*/
        });
    });
});

</script>
</body>
</html>

');
    }

    public function detail($directory) {
        $data = array();
        $data['size'] = \FileSystem::calculateDirectorySize($directory);
        $data['countOfObjects'] = 100;
        echo json_encode($data);
    }
}

