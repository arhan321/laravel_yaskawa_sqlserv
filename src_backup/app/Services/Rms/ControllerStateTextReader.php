<?php

declare(strict_types=1);

namespace App\Services\Rms;

use App\Models\Robot;

/** Diagnostic-only extraction of controller state already stored in raw_payload. */
final class ControllerStateTextReader
{
    public function latest(Robot $robot): ?array
    {
        $items=$robot->telemetrySamples()->where('sampled_at','>=',now()->subSeconds(60))
            ->orderByDesc('sampled_at')->limit(20)->get();
        foreach($items as $item){
            $raw=$item->raw_payload;
            if (!is_array($raw) || ($raw['simulated']??false)===true) continue;
            $text=$raw['controller_state']['sdk_state_text']??($raw['state_read']['raw']??null);
            if (!is_string($text) || strlen($text)>8192) continue;
            $control=$this->field($text,'Control Mode');
            $running=$this->boolean($text,'Is Running');
            $servo=$this->boolean($text,'Is Servo On');
            $alarm=$this->boolean($text,'Is Alarming');
            $hold=$this->boolean($text,'Is Program Pendant Hold');
            if ($control===null && $running===null && $servo===null) continue;
            return [
                'source'=>'sdk_state_text_unverified',
                'sampled_at'=>$item->sampled_at?->toIso8601String(),
                'control_mode'=>$control,
                'running'=>$running,
                'servo_on'=>$servo,
                'alarming'=>$alarm,
                'pendant_hold'=>$hold,
                // This is diagnostic text and not a safety-rated servo state.
                'verified'=>false,
            ];
        }
        return null;
    }

    private function field(string $text,string $name): ?string
    {
        if(!preg_match('/^'.preg_quote($name,'/').'\s*:\s*([^\r\n]+)/mi',$text,$m)) return null;
        $v=trim($m[1]);
        return strlen($v)>60 ? null : $v;
    }

    private function boolean(string $text,string $name): ?bool
    {
        $v=$this->field($text,$name);
        return $v===null ? null : match(strtolower($v)){
            'true'=>true,'false'=>false,default=>null,
        };
    }
}
