<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Process;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class Aga8Service
{
    protected string $driver;

    protected string $pythonPath;

    protected string $scriptPath;

    protected int $timeout;

    public function __construct()
    {
        $this->driver = (string) config('services.aga8.driver', 'native');
        $this->pythonPath = (string) config('services.aga8.python_path', 'python');
        $this->scriptPath = (string) config('services.aga8.script_path', base_path('python/aga8/bridge.py'));
        $this->timeout = (int) config('services.aga8.timeout', 15);
    }

    /**
     * Check if process execution (proc_open CLI) is allowed and available in current PHP runtime.
     */
    public function canUseProcess(): bool
    {
        if (! function_exists('proc_open') || ! function_exists('proc_close')) {
            return false;
        }

        $disabled = explode(',', (string) ini_get('disable_functions'));
        $disabled = array_map('trim', $disabled);

        if (in_array('proc_open', $disabled, true) || in_array('proc_close', $disabled, true)) {
            return false;
        }

        return file_exists($this->scriptPath);
    }

    /**
     * Resolve the active execution driver ('native' or 'process').
     */
    public function getActiveDriver(): string
    {
        $driver = strtolower(trim($this->driver));

        if ($driver === 'process' && $this->canUseProcess()) {
            return 'process';
        }

        return 'native';
    }

    /**
     * Set driver dynamically (useful for testing or runtime overrides).
     */
    public function setDriver(string $driver): self
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * حساب الخصائص الفيزيائية للغاز الطبيعي وفق معايير ISO 6976 و AGA8 Detail.
     *
     * @param  float  $pressureKpa  الضغط بالكيلوباسكال (kPa)
     * @param  float  $temperatureK  درجة الحرارة بالكلفن (K)
     * @param  array<string, float>|array<int, float>  $composition  نسب الغاز المولية
     * @return array<string, mixed> نتائج الحسابات الفيزيائية
     */
    public function calculate(float $pressureKpa, float $temperatureK, array $composition): array
    {
        if ($pressureKpa <= 0) {
            throw new InvalidArgumentException('الضغط يجب أن يكون أكبر من الصفر (kPa).');
        }

        if ($temperatureK <= 0) {
            throw new InvalidArgumentException('درجة الحرارة يجب أن تكون أكبر من الصفر المطلق (Kelvin).');
        }

        if (empty($composition)) {
            throw new InvalidArgumentException('يجب تزويد نسب مكونات الغاز (Gas Composition).');
        }

        $driver = strtolower(trim($this->driver));

        if ($driver === 'process' && $this->canUseProcess()) {
            try {
                return $this->calculateViaProcess($pressureKpa, $temperatureK, $composition);
            } catch (Throwable) {
                // Seamless fallback to native
            }
        }

        // Default & reliable: Autonomous Native Pure PHP engine (ISO 6976:1995)
        return $this->calculateViaNative($pressureKpa, $temperatureK, $composition);
    }

    /**
     * الحساب الذاتي الأصيل داخل PHP (Autonomous Native Engine - بدون بايثون أو خوادم خارجية).
     *
     * @param  array<string, float>|array<int, float>  $composition
     * @return array<string, mixed>
     */
    public function calculateViaNative(float $pressureKpa, float $temperatureK, array $composition): array
    {
        $res = Iso6976Engine::calculate($composition, $pressureKpa, $temperatureK);

        return array_merge($res['results'] ?? [], [
            'energy_properties_iso6976' => $res['energy_properties_iso6976'] ?? [],
            'thermodynamic_properties_aga8' => $res['thermodynamic_properties_aga8'] ?? [],
            'base_thermodynamic_properties_aga8' => $res['base_thermodynamic_properties_aga8'] ?? [],
        ]);
    }

    /**
     * حساب الخصائص عبر عملية بايثون محلية (CLI Process).
     *
     * @param  array<string, float>|array<int, float>  $composition
     * @return array<string, mixed>
     */
    public function calculateViaProcess(float $pressureKpa, float $temperatureK, array $composition): array
    {
        $payload = json_encode([
            'pressure_kpa' => $pressureKpa,
            'temperature_k' => $temperatureK,
            'composition' => $composition,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $process = Process::input($payload)
            ->timeout($this->timeout)
            ->run([$this->pythonPath, $this->scriptPath]);

        if ($process->failed()) {
            $errorOutput = trim($process->errorOutput());
            throw new RuntimeException('فشل تنفيذ سكريبت بايثون AGA8: '.($errorOutput ?: $process->output()));
        }

        $response = json_decode($process->output(), true);

        if (! $response || ! isset($response['success'])) {
            throw new RuntimeException('استجابة غير صالحة من سكريبت بايثون: '.$process->output());
        }

        if (! $response['success']) {
            throw new RuntimeException('خطأ في حسابات AGA8: '.($response['error'] ?? 'خطأ غير معروف'));
        }

        return array_merge($response['results'] ?? [], [
            'energy_properties_iso6976' => $response['energy_properties_iso6976'] ?? [],
            'thermodynamic_properties_aga8' => $response['thermodynamic_properties_aga8'] ?? [],
            'base_thermodynamic_properties_aga8' => $response['base_thermodynamic_properties_aga8'] ?? [],
        ]);
    }

    /**
     * حساب خواص الغاز عند الشروط القياسية المعتمدة (15 °C و 101.325 kPa) وفق ISO 6976 و AGA8
     *
     * @param  array<string, float>|array<int, float>  $composition
     * @return array<string, mixed>
     */
    public function calculateBaseAndEnergy(array $composition): array
    {
        return $this->calculateWithUnits(
            pressure: 101.325,
            pressureUnit: 'kpa',
            temperature: 15.0,
            tempUnit: 'c',
            composition: $composition
        );
    }

    /**
     * حساب الخواص مع دعم تلقائي لتحويل وحدات الضغط والحرارة.
     *
     * @param  float  $pressure  قيمة الضغط
     * @param  string  $pressureUnit  وحدة الضغط: 'kpa', 'bar', 'psi', 'mpa', 'atm'
     * @param  float  $temperature  قيمة درجة الحرارة
     * @param  string  $tempUnit  وحدة الحرارة: 'k', 'c', 'f'
     * @param  array<string, float>|array<int, float>  $composition  نسب مكونات الغاز
     * @return array<string, mixed>
     */
    public function calculateWithUnits(
        float $pressure,
        string $pressureUnit,
        float $temperature,
        string $tempUnit,
        array $composition
    ): array {
        $pressureKpa = match (strtolower(trim($pressureUnit))) {
            'kpa' => $pressure,
            'bar' => $pressure * 100.0,
            'mpa' => $pressure * 1000.0,
            'psi' => $pressure * 6.894757,
            'atm' => $pressure * 101.325,
            default => throw new InvalidArgumentException("وحدة ضغط غير مدعومة: {$pressureUnit}"),
        };

        $temperatureK = match (strtolower(trim($tempUnit))) {
            'k', 'kelvin' => $temperature,
            'c', 'celsius' => $temperature + 273.15,
            'f', 'fahrenheit' => ($temperature - 32.0) * (5.0 / 9.0) + 273.15,
            default => throw new InvalidArgumentException("وحدة حرارة غير مدعومة: {$tempUnit}"),
        };

        return $this->calculate($pressureKpa, $temperatureK, $composition);
    }

    /**
     * اختبار الاتصال بمحرك AGA8 وتشغيل فحص قياسي.
     *
     * @param  string|null  $driver  مشغل محدد ('native' أو 'process' أو null)
     * @return array<string, mixed>
     */
    public function testConnection(?string $driver = null): array
    {
        $targetDriver = $driver ? strtolower(trim($driver)) : $this->getActiveDriver();

        if ($targetDriver === 'process' && $this->canUseProcess()) {
            try {
                $process = Process::timeout($this->timeout)->run([$this->pythonPath, $this->scriptPath, '--test']);
                if ($process->successful()) {
                    $res = json_decode($process->output(), true) ?? [];
                    $res['_driver_used'] = 'process';

                    return $res;
                }
            } catch (Throwable) {
                // fall through to native
            }
        }

        // Native pure PHP engine (guaranteed to succeed everywhere including cPanel)
        $data = Iso6976Engine::calculate([
            'Methane' => 0.95,
            'Nitrogen' => 0.05,
        ], 50000.0, 400.0);
        $data['_driver_used'] = 'native';

        return $data;
    }

    /**
     * جلب قائمة المكونات الـ 21 المعتمدة وأسمائها وصيغها الكيميائية.
     *
     * @param  string|null  $driver  مشغل محدد ('native' أو 'process' أو null)
     * @return array<int, array<string, mixed>>
     */
    public function getComponents(?string $driver = null): array
    {
        $targetDriver = $driver ? strtolower(trim($driver)) : $this->getActiveDriver();

        if ($targetDriver === 'process' && $this->canUseProcess()) {
            try {
                $process = Process::timeout($this->timeout)->run([$this->pythonPath, $this->scriptPath, '--components']);
                if ($process->successful()) {
                    $res = json_decode($process->output(), true);

                    return $res['components'] ?? Iso6976Engine::getComponentsInfo();
                }
            } catch (Throwable) {
                // fall through
            }
        }

        return Iso6976Engine::getComponentsInfo();
    }
}
