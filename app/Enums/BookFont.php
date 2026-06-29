<?php

namespace App\Enums;

/**
 * The font families exposed to authors, grouped by script. The `value` is the
 * stored key; `family()` is the CSS/mpdf font-family name; `script()` groups
 * them in the UI font picker and decides RTL handling.
 *
 * All families are bundled locally for PDF generation (see the PDF service),
 * so there is no runtime font download.
 */
enum BookFont: string
{
    // Latin (English / French)
    case EbGaramond = 'eb_garamond';
    case PlayfairDisplay = 'playfair_display';
    case Merriweather = 'merriweather';
    case Lora = 'lora';
    case CrimsonPro = 'crimson_pro';
    case LibreBaskerville = 'libre_baskerville';
    case Cardo = 'cardo';

    // Arabic
    case Amiri = 'amiri';
    case Cairo = 'cairo';
    case NotoNaskhArabic = 'noto_naskh_arabic';
    case Tajawal = 'tajawal';
    case ElMessiri = 'el_messiri';
    case Lateef = 'lateef';
    case ReemKufi = 'reem_kufi';

    public function label(): string
    {
        return match ($this) {
            self::EbGaramond => 'EB Garamond',
            self::PlayfairDisplay => 'Playfair Display',
            self::Merriweather => 'Merriweather',
            self::Lora => 'Lora',
            self::CrimsonPro => 'Crimson Pro',
            self::LibreBaskerville => 'Libre Baskerville',
            self::Cardo => 'Cardo',
            self::Amiri => 'Amiri',
            self::Cairo => 'Cairo',
            self::NotoNaskhArabic => 'Noto Naskh Arabic',
            self::Tajawal => 'Tajawal',
            self::ElMessiri => 'El Messiri',
            self::Lateef => 'Lateef',
            self::ReemKufi => 'Reem Kufi',
        };
    }

    /**
     * CSS / mpdf font-family name.
     */
    public function family(): string
    {
        return $this->label();
    }

    /**
     * Script group: 'latin' or 'arabic'. Used to group the picker and to pick
     * RTL rendering for Arabic.
     */
    public function script(): string
    {
        return match ($this) {
            self::Amiri,
            self::Cairo,
            self::NotoNaskhArabic,
            self::Tajawal,
            self::ElMessiri,
            self::Lateef,
            self::ReemKufi => 'arabic',
            default => 'latin',
        };
    }

    public function isArabic(): bool
    {
        return $this->script() === 'arabic';
    }

    /**
     * mpdf font-family key. Each value maps to a bundled family registered via
     * {@see self::mpdfFontData()}; the TTF files live in storage/fonts/.
     */
    public function mpdfFamily(): string
    {
        return match ($this) {
            self::EbGaramond => 'ebgaramond',
            self::PlayfairDisplay => 'playfairdisplay',
            self::Merriweather => 'merriweather',
            self::Lora => 'lora',
            self::CrimsonPro => 'crimsonpro',
            self::LibreBaskerville => 'librebaskerville',
            self::Cardo => 'cardo',
            self::Amiri => 'amiri',
            self::Cairo => 'cairo',
            self::NotoNaskhArabic => 'notonaskharabic',
            self::Tajawal => 'tajawal',
            self::ElMessiri => 'elmessiri',
            self::Lateef => 'lateef',
            self::ReemKufi => 'reemkufi',
        };
    }

    /**
     * mpdf `fontdata` entries for every bundled family, keyed by {@see mpdfFamily()}.
     * Arabic families enable OpenType layout + kashida so glyphs shape and join.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function mpdfFontData(): array
    {
        $data = [];

        foreach (self::cases() as $font) {
            $key = $font->mpdfFamily();
            $entry = [
                'R' => "{$key}-Regular.ttf",
                'B' => "{$key}-Bold.ttf",
            ];

            if ($font->isArabic()) {
                $entry['useOTL'] = 0xFF;
                $entry['useKashida'] = 75;
            }

            $data[$key] = $entry;
        }

        return $data;
    }
}
