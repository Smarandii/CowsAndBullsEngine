<?php
declare(strict_types=1);

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);

    return round($bytes, $precision) . ' ' . $units[$pow];
}


class CowsAndBullsEngine {
    private int $startNumber;
    private int $stopNumber;

    private array $result = [
        'numbers' => 0, //количество чисел для отгадывания из диапазона которые прошли валидацию и участвовали в игре
        'triesSum' => 0, //суммарное количество всех попыток для всех чисел
        'triesMin' => INF, //минимальное число попыток которое потребовалось для вычисление одного числа
        'triesMax' => 0, //максимальное число попыток которое потребовалось для вычисление одного числа
        'microtime' => 0.0, //время в секундах которое потребовалось на вычисление всех числе из диапазона (время работы функции)
    ];

    public function __construct($startNumber, $stopNumber)
    {
        $this->startNumber = $startNumber;
        $this->stopNumber = $stopNumber;
    }

    function getIntAsArray(int $number): array
    {
        return [ (int)($number / 1000), (int)($number % 1000 / 100), (int)($number % 100 / 10), $number % 10 ];
    }

    function getCows(array $numberArray, array $tryNumberArray): int
    {
        return count(array_intersect($numberArray, $tryNumberArray));
    }

    function getBulls(array $numberArray, array $tryNumberArray): int
    {
        $bulls = 0;
        for ($i=0, $iMax = count($numberArray); $i < $iMax; $i++) {
            if ($numberArray[$i] == $tryNumberArray[$i]) {
                $bulls+=1;
            }
        }
        return $bulls;
    }

    function validateNumber(int $number): bool
    {
        if ($number < 1000) {
            return false;
        }
        $digits = $this->getIntAsArray($number);
        $count_values = array_count_values($digits);
        foreach ( $count_values as $item) {;
            if ($item > 1)
                return false;
        }
        return true;
    }


    function getCowsAndBulls(int $number, int $tryNumber): array {
        $numberArray = $this->getIntAsArray($number);
        $tryNumberArray = $this->getIntAsArray($tryNumber);
        $cows = $this->getCows($numberArray, $tryNumberArray);
        $bulls = $this->getBulls($numberArray, $tryNumberArray);
        return ["cows" => $cows, "bulls" => $bulls];
    }


    function generateAllPossibleNumbers(): array {
        $numbers = [];

        for ($i = $this->startNumber; $i <= $this->stopNumber; $i++) {
            if ($this->validateNumber($i)) {
                $numbers[] = $i;
            }
        }

        return $numbers;
    }

    function getRandomPossibleNumber(array $possibleNumbers): int {
        return $possibleNumbers[array_rand($possibleNumbers)];
    }

    function playBullsAndCows(int $number): int {
        $possibleNumbers = $this->generateAllPossibleNumbers();
        $prevResults = [];

        while (true) {

            $tryNumber = $this->getRandomPossibleNumber($possibleNumbers);
            $cowsAndBulls = $this->getCowsAndBulls($number, $tryNumber);
            $prevResults[] = [$tryNumber => $cowsAndBulls];

            if ($cowsAndBulls['bulls'] == 4) {
                // echo $tryNumber . PHP_EOL;
                return count($prevResults);
            }

            $possibleNumbers = array_filter($possibleNumbers, function ($possibleNumber) use ($cowsAndBulls, $tryNumber) {
                $comparison = $this->getCowsAndBulls($possibleNumber, $tryNumber);
                return $comparison['bulls'] == $cowsAndBulls['bulls'] && $comparison['cows'] == $cowsAndBulls['cows'];
            });
        }
    }



    function updateResult(int $i): void
    {
        $time_before = microtime(true);
        $tries_for_number_i = $this->playBullsAndCows($i);
        $time_after = microtime(true);

        $this->result['numbers'] += 1;
        $this->result['triesSum'] += $tries_for_number_i;
        if ($tries_for_number_i < $this->result['triesMin'])
            $this->result['triesMin'] = $tries_for_number_i;
        if ($tries_for_number_i > $this->result['triesMax'])
            $this->result['triesMax'] = $tries_for_number_i;
        $this->result['microtime'] += ($time_after - $time_before);
    }

    function playBullsAndCowsWithRange(): array {
        $possibleNumbers = $this->generateAllPossibleNumbers();
        //$count = 0;
        foreach ($possibleNumbers as $possibleNumber) {
            $this->updateResult($possibleNumber);
            //$count++;
            //echo round($count / count($possibleNumbers) * 100, 0) . " %"  . PHP_EOL;
        }
        return $this->result;
    }
}

$memoryBefore = memory_get_usage();

$startNumber = 1234;
$stopNumber = 9876;
$engine = new CowsAndBullsEngine($startNumber, $stopNumber);
$result = $engine->playBullsAndCowsWithRange();
echo "Для вычисления чисел в диапазоне от $startNumber до $stopNumber потребовалось " . $result['triesSum'] . " попыток и " . $result['microtime'] . " секунд." . PHP_EOL;
echo "Максимальное число попыток " . $result['triesMax'] . PHP_EOL;
echo "Минимальное число попыток " . $result['triesMin'] . PHP_EOL;
echo "Среднее количество попыток " . round($result['triesSum'] / $result['numbers']) . PHP_EOL;

$memoryAfter = memory_get_usage();
$memoryUsed = $memoryAfter - $memoryBefore;
echo "Кол-во памяти использованное алгоритмом: " . formatBytes($memoryUsed) . PHP_EOL;