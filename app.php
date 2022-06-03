<?php
# Решил использовать хранение информации в файлах для упрощения. Можно конечно развернуть базу данных или использовать nosql решение, но в целом для данной задачи я думаю это не к чему

class CommandHelper {
    private $path = 'commands'.DIRECTORY_SEPARATOR;
    public function showHelp() {
        echo '
Данная команда используется следующим образом
Для создания новой команды:
    php app.php command_name {verbose,overwrite} [log_file=app.log] {unlimited} [methods={create,update,delete}] [paginate=50] {log}
Для заполнения описания команды
    php app.php command_name {help} описание команды
Для просмотра описания команды
    php app.php command_name {help}
	';
    }
    public function showCommandHelp($commandName) {
        # на всякий случай берём basename, чтобы в командах не пытались сохранить информацию в других директория
        $file = $this->path.basename($commandName).'.json';
        if (!file_exists(($file))) {
            echo 'Такой команды не существует';
            return;
        }
        $json = json_decode(file_get_contents(($file)), true);
        if (isset($json['desc']) && !empty($json['desc'])) echo $json['desc'];
        else echo 'Описание отсутствует';
    }
    public function showCommandParams($commandName) {
        # на всякий случай берём basename, чтобы в командах не пытались сохранить информацию в других директория
        $file = $this->path.basename($commandName).'.json';
        if (!file_exists(($file))) {
            echo 'Такой команды не существует';
            return;
        }
        $json = json_decode(file_get_contents(($file)), true);

        $this->showJsonParams($commandName, $json);
    }
    private function showJsonParams($commandName, $json) {
        echo "Called command: $commandName\n";
        if (isset($json['arguments']) && !empty($json['arguments'])) {
            echo "Arguments:\n";
            foreach ($json['arguments'] as $oneArgument) {
                echo "  - $oneArgument\n";
            }
        }
        if (isset($json['options']) && !empty($json['options'])) {
            echo "Options:\n";
            foreach ($json['options'] as $optionName=>$optionParams) {
                echo "  - $optionName\n";
                foreach ($optionParams as $oneOptionParam) {
                    echo "    - $oneOptionParam\n";
                }
            }
        }
    }
    public function createCommandDesc($commandName, $params) {
        array_shift($params);
        $desc = implode(' ', $params);
        $this->saveCommand($commandName, ['desc'=>$desc]);
    }
    public function createCommand($commandName, $params) {
        $allArguments = [];
        $allOptions = [];

        foreach ($params as $oneParam) {
            if (substr($oneParam,0,1) == '{') {
                $allArguments = array_merge($allArguments, explode(',', trim($oneParam, '{}')));
            }
            if (substr($oneParam,0,1) == '[') {
                $oneParam = trim($oneParam, '[]');
                list($key, $value) = explode('=', $oneParam);

                if (substr($value,0,1) == '{') {
                    $value = trim($value, '{}');
                    $value = explode(',', $value);
                }
                else $value = [$value];
                $allOptions[$key] = $value;
            }
        }
        $this->saveCommand($commandName, ['options'=>$allOptions,'arguments'=>$allArguments]);
    }
    private function saveCommand($commandName, $params) {
        $path = 'commands'.DIRECTORY_SEPARATOR;
        # на всякий случай берём basename, чтобы в командах не пытались сохранить информацию в других директория
        $file = $path.basename($commandName).'.json';
        if (file_exists($file)) {
            $json = json_decode(file_get_contents($file), true);
            if (!is_array($json)) $json = [];
            $json = json_encode(array_merge($json, $params));
        }
        else $json = json_encode($params);

        file_put_contents($file, $json);
        if (isset($json['options']) || isset($json['arguments'])) $this->showJsonParams($commandName, $json);
    }
}
$commandHelper = new CommandHelper();

if ($argc == 2 && $argv[1] == '{help}') {
    $commandHelper->showHelp();
	return;
}

$params = $argv;
array_shift($params);
$commandName = array_shift($params);

if ($argc == 3 && $argv[2] == '{help}') {
    $commandHelper->showCommandHelp($commandName);
    return;
}
if ($argc > 3 && $argv[2] == '{help}') {
    $commandHelper->createCommandDesc($commandName, $params);
    return;
}
if ($argc == 2) {
    $commandHelper->showCommandParams($commandName);
    return;
}

$commandHelper->createCommand($commandName, $params);

//$str = '{verbose,overwrite} [log_file=app.log] {unlimited} [methods={create,update,delete}] [paginate=50] {log}';
//preg_match_all('/[\[].+?[\]]/', $str, $options);
//preg_match_all('/[\{].+?[\}]/', $str, $arguments);
//foreach ($arguments[0] as $oneArgument) {
//	$allArguments = array_merge($allArguments, explode(',', trim($oneArgument, '{}')));
//}