<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions\Pdf;

use BonyadAlavi\FormEngine\Actions\ActionContext;
use RuntimeException;

final class PdfGenerator
{
    /** @return array{path:string,url:string,filename:string} */
    public function generate(ActionContext $context, string $filenameTemplate = ''): array
    {
        if (!class_exists('\\Dompdf\\Dompdf')) {
            throw new RuntimeException('برای تولید PDF سمت سرور، Dompdf در سایت در دسترس نیست.');
        }
        $uploads = wp_upload_dir();
        if (!empty($uploads['error'])) throw new RuntimeException('مسیر Upload وردپرس در دسترس نیست.');
        $dir = trailingslashit((string)$uploads['basedir']).'alavi-form-engine/generated';
        if (!wp_mkdir_p($dir)) throw new RuntimeException('ساخت پوشه PDFهای تولیدشده ناموفق بود.');

        $filename = sanitize_file_name($filenameTemplate);
        if ($filename === '') $filename = 'submission-'.$context->submissionId.'.pdf';
        if (!str_ends_with(strtolower($filename), '.pdf')) $filename .= '.pdf';
        $nonce = bin2hex(random_bytes(8));
        $filename = preg_replace('/\.pdf$/i', '-'.gmdate('Ymd-His').'-'.$nonce.'.pdf', $filename) ?: $filename;
        $path = trailingslashit($dir).$filename;

        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled'=>false]);
        $dompdf->loadHtml($this->html($context), 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $bytes = file_put_contents($path, $dompdf->output());
        if ($bytes === false || $bytes <= 0) throw new RuntimeException('ذخیره فایل PDF تولیدشده ناموفق بود.');

        $url = trailingslashit((string)$uploads['baseurl']).'alavi-form-engine/generated/'.rawurlencode($filename);
        return ['path'=>$path,'url'=>$url,'filename'=>$filename];
    }

    private function html(ActionContext $context): string
    {
        $rows = '';
        foreach ($this->fields($context->form) as $path=>$label) {
            $value = $this->valueByPath($context->data, $path);
            if (is_array($value)) $value = wp_json_encode($value, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) ?: '';
            $rows .= '<tr><th>'.esc_html($label).'</th><td>'.nl2br(esc_html((string)$value)).'</td></tr>';
        }
        if ($rows === '') {
            foreach ($context->data as $key=>$value) {
                if (is_array($value)) $value = wp_json_encode($value, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) ?: '';
                $rows .= '<tr><th>'.esc_html((string)$key).'</th><td>'.nl2br(esc_html((string)$value)).'</td></tr>';
            }
        }
        $tracking = (string)($context->submission['tracking_code'] ?? '');
        return '<!doctype html><html dir="rtl" lang="fa"><head><meta charset="utf-8"><style>'
            .'body{font-family:DejaVu Sans,Tahoma,sans-serif;direction:rtl;color:#17231f;font-size:11px}h1{font-size:18px;color:#0f6b4f}.meta{color:#55635e;margin-bottom:16px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #dce4e0;padding:8px;text-align:right;vertical-align:top}th{width:30%;background:#f4f8f6}'
            .'</style></head><body><h1>'.esc_html($context->formTitle ?: $context->formSlug).'</h1><div class="meta">Submission #'.(int)$context->submissionId.' · '.esc_html($tracking).'</div><table>'.$rows.'</table></body></html>';
    }

    /** @return array<string,string> */
    private function fields(array $form): array
    {
        $out = [];
        foreach ((array)($form['steps'] ?? []) as $step) {
            foreach ((array)($step['items'] ?? []) as $field) $this->collectField((array)$field, '', $out);
        }
        return $out;
    }

    /** @param array<string,string> $out */
    private function collectField(array $field, string $prefix, array &$out): void
    {
        $type = (string)($field['type'] ?? '');
        $name = (string)($field['name'] ?? '');
        if ($name === '' || in_array($type, ['html','file'], true)) return;
        $path = $prefix === '' ? $name : $prefix.'.'.$name;
        if ($type === 'repeater') {
            foreach ((array)($field['fields'] ?? []) as $child) $this->collectField((array)$child, $path, $out);
            return;
        }
        $out[$path] = (string)($field['label'] ?? $path);
    }

    private function valueByPath(mixed $value, string $path): mixed
    {
        $segments = explode('.', $path);
        return $this->walk($value, $segments);
    }

    private function walk(mixed $value, array $segments): mixed
    {
        if ($segments === []) return $value;
        if (!is_array($value)) return null;
        $segment = (string)$segments[0];
        $remaining = array_slice($segments, 1);
        if (array_key_exists($segment, $value)) return $this->walk($value[$segment], $remaining);
        if (array_is_list($value)) {
            $out = [];
            foreach ($value as $row) {
                $resolved = $this->walk($row, $segments);
                if ($resolved !== null) $out[] = $resolved;
            }
            return $out;
        }
        return null;
    }
}
