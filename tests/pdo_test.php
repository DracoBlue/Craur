<?php

#$craur = Craur::createFromPdo('mysql:host=127.0.0.1;dbname=dbtest', array(
#    'user' => 'root',
#    'password' => 'root'
#));
#print_r($craur);

$temp_file = tempnam(sys_get_temp_dir(), 'CraurTests');

function delete_temp_sqlite_file()
{
    global $temp_file;
    @unlink($temp_file);
}

register_shutdown_function('delete_temp_sqlite_file');

$pdo = new PDO('sqlite:' . $temp_file);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->prepare('CREATE TABLE first_table (id INTEGER PRIMARY KEY ASC, name TEXT, description TEXT);')->execute();
$pdo->prepare('INSERT INTO first_table (id, name, description) VALUES (1, \'Alice\', \'This is Alice\');')->execute();
$pdo->prepare('INSERT INTO first_table (id, name, description) VALUES (3, \'Bob\', \'This is about Bob\');')->execute();
$pdo->prepare('INSERT INTO first_table (id, name, description) VALUES (12, \'Carl\', \'And that is about Carl\')')->execute();

$craur = Craur::createFromPdo('sqlite:' . $temp_file, array(
        'tables' => array('first_table')
));

$expected_output = <<< EOT
INSERT INTO `first_table` (`id`,`name`,`description`) VALUES ('1','Alice','This is Alice');
INSERT INTO `first_table` (`id`,`name`,`description`) VALUES ('3','Bob','This is about Bob');
INSERT INTO `first_table` (`id`,`name`,`description`) VALUES ('12','Carl','And that is about Carl')
EOT;

assert(trim($craur->toMysqlStatements()) == trim($expected_output));

