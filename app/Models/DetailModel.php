<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailModel extends Model {
    protected float $initialDiameter = 0.0; // начальный диаметр детали
    protected array $damage = []; //поврежденные участки
    protected float $type = 0; //тип детали
    private float $Li = 0.0; // общая длина обрабатываемой поверхности
    private float $damageDiameter = 0.0; // диаметр который меняется в процессе работы

    public function doDetail()
    {
        $this->turningTime();
        if ($this->type == 1 || $this->type == 2) {
            $this->electricityWelding();
        } else {
            $this->vibroWelding();
        }

        $thirdStep = $this->turningTime() + $this->thirdTurningTime();
        $this->grinding();

        // сопоставить в матрице тип станка и время работы для этой детали на каждом станке
        //вернуть общее время одной детали

        //сложить допвремя в матрицу чтобы вернуть матрицу переналадок
    }

    // токарные работы l = 1, токарные работы при продольном точении - l = 3
    function turningTime(
        float $yi = 3.5,  // величина врезания и перебега для детали
        int $ki = 1,       // количество проходов для снятия припуска при глубине резания
        float $s1i = 0.5,     // подача режущего инструмента
        float $v1i = 50,     // скорость резания
        float $tv1i = 3     // длительность вспомогательных операций токарной обработки
    ): float {
        $lenght = count($this->damage) * ($this->lenghtDetail(array_sum($this->damage), $yi)); // длина обрабатываемой поверхности детали с учетом врезания и пробега
        $this -> calculateNewDiameter($this->initialDiameter, $this ->damage); // диаметр повреждений
        $n1i = 318 * ($v1i / $this->initialDiameter); // число оборотов деталей в минуту

        // Расчет длительности основной токарной обработки
        $to1i = (($lenght * $ki) / ($n1i * $s1i));

        // Возврат суммы основной и вспомогательной длительности обработки
        return $to1i + $tv1i;
    }

    // длина обрабатываемой поверхности детали с учетом врезания и пробега
    private function lenghtDetail(
        float $li, // длина обрабатываемой поверхности по чертежу (обработка поврежденной поверхности)
        float $yi // величина врезания и перебега для детали
    ) {
        $this->Li = $li + $yi;
    }

    private function calculateNewDiameter(
        float $initialDiameter, // начальный диаметр
        array $wears //массив повреждений
    ) {
        // Суммарный износ
        $totalWear = array_sum($wears);

        // Диаметр повреждения = начальный диаметр - суммарный износ
        $this -> damageDiameter = $initialDiameter - $totalWear;
    }

    //электродуговая наплавка l = 2
    function electricityWelding (
        float $k2i = 1, // количество проходов при наплавке
    ) : float {

        $density =  7.81;// плотность проволки
        $lenghtDetail = $this->Li; // длина обрабатываемой поверхности детали с учетом врезания и пробега
        $dpri = ($this -> initialDiameter) - ($this -> damageDiameter); //диаметр проволки (начальный диаметр - диаметр детали после обработки)
        $Isv2i = (40 * pow($this -> initialDiameter, 1/3)); // сварочный ток (А)
        $K2ni = (2.3 + 0.065 * ($Isv2i / $dpri )); //коэффициент наплавки
        $v2ni = ((4 * $K2ni * $Isv2i) / pi() * pow($this -> initialDiameter, 2) * $density); //скорость наплавки
        $n2i = ((1000 * $v2ni) / (60 * pi() * $this -> initialDiameter)); // частота вращения детали
        $s2i = $this ->stepWelding($dpri,  2,  2.5); //шаг наплавки детали

        //основное время
        $to2i = (($k2i * $lenghtDetail) / ($n2i * $s2i));
        //вспомогательное время
        $tv2i = (($to2i * 15) / 100);

        return $to2i + $tv2i;
    }

    //вычисление шага наплавки
    private function stepWelding(float $dpri, float $minFactor, float $maxFactor): float {
        // Выбираем случайный коэффициент в диапазоне от minFactor до maxFactor
        $factor = mt_rand($minFactor * 100, $maxFactor * 100) / 100;

        return $factor * $dpri;
    }

    // вибродуговая наплавка l = 2

    function vibroWelding(
        float $k2i = 2, // количество проходов при наплавке
        float $h2vi = 3 // толщина наплавляемого слоя
    ) : float {

        // расчет основных переменных для общего времени
        $nu = 0.8 / 0.9; //коэффициент перехода
        $alpha = 0.7 / 0.85; // коэффициент отклонения толщины
        $dpri = $this -> initialDiameter - $this -> damageDiameter; // диаметр проволки
        $Ui = $this -> voltage($h2vi); // напряжение
        $lenghtDetail = $this->Li; // длина обрабатываемой поверхности детали с учетом врезания и пробега
        $Isv2i = $this->electricity($dpri); // сварочный ток (А)
        $s2i = $this ->stepWelding($dpri,  1.6,  2.2); //шаг наплавки
        $vni = (0.1 * $Isv2i * $Ui) / $dpri; //величина подачи проволки

        $v2vi = ((0.785 * pow($dpri, 2) * $vni * $nu) / ($h2vi * $s2i * $alpha));//скорость наплавки
        $n2i = ((1000 * $v2vi) / (60 * pi() * $this -> initialDiameter)); // частота вращения детали

        //основное время
        $to2i = (($k2i * $lenghtDetail) / ($n2i * $s2i));
        //вспомогательное время
        $tv2i = (($to2i * 15) / 100);
        return $to2i + $tv2i;
    }

    private function electricity(float $dpri): float {
        $factor = mt_rand(60 * 100, 75 * 100) / 100;
        return $factor * $dpri;
    }

    private  function voltage(float $h2vi): float {
        $Ui = 0.0;
        if ($h2vi >= 1.0 && $h2vi <= 1.5) {
            $Ui = rand(15, 20); // случайное значение в диапазоне 15-20 В
        } elseif ($h2vi >= 2.0 && $h2vi <= 2.5) {
            $Ui = rand(20, 25); // случайное значение в диапазоне 20-25 В
        }
        return $Ui;
    }

    function thirdTurningTime(
        float $yi = 3.5,  // величина врезания и перебега для детали
        int $ki = 1,       // количество проходов для снятия припуска при глубине резания
        float $s1i = 0.5,     // подача режущего инструмента
        float $v1i = 50,     // скорость резания
        float $tv1i = 3     // длительность вспомогательных операций токарной обработки
    ): float {
        $lenghtDetail = count($this->damage) * ($this->lenghtDetail(($this -> initialDiameter / 2), $yi)); // длина обрабатываемой поверхности детали с учетом врезания и пробега
        $this -> calculateNewDiameter($this->initialDiameter, $this ->damage); // диаметр повреждений
        $n1i = 318 * ($v1i / $this->initialDiameter); // число оборотов деталей в минуту

        // Расчет длительности основной токарной обработки
        $to1i = (($lenghtDetail * $ki) / ($n1i * $s1i));

        // Возврат суммы основной и вспомогательной длительности обработки
        return $to1i + $tv1i;
    }

    //шлифование l = 4
    public function grinding(
        float $snp4i = 0.4, // продольная подача инструментов
    ) : float {
        $lenghtDetail = $this->Li; // длина детали
        $ki4 = rand(4, 10); // количество проходов
        $v4i = rand(0.4,0.6); // скорость шлифования деталей
        $n4i = 318 * ($v4i / $this-> initialDiameter);
        $K = mt_rand(1.2, 1.7);

        //вспомогательное время
        $tv4i = rand(2.7, 3.4);

        //основное время
        $to4i = (($lenghtDetail * $ki4) / ($n4i * $snp4i)) * $K;

        return $to4i + $tv4i;
    }
}
