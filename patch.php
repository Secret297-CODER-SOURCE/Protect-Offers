#!/usr/bin/env php
<?php

declare(strict_types=1);

const OG_PATCH_BUILD = '2026-05-28-merged-v11';

const R    = "\033[0m";
const B    = "\033[1m";
const RED  = "\033[31m";
const GREEN= "\033[32m";
const YEL  = "\033[33m";
const CYAN = "\033[36m";
const GRAY = "\033[90m";
const MAG  = "\033[35m";

function out(string $m): void { echo $m . R . "\n"; }
function ok(string $m):  void { out(GREEN  . "  ✓  $m"); }
function fail(string $m):void { out(RED    . "  ✗  $m"); }
function info(string $m):void { out(CYAN   . "  →  $m"); }
function warn(string $m):void { out(YEL    . "  !  $m"); }
function skip(string $m):void { out(GRAY   . "  –  $m"); }
function head(string $m):void { out("\n" . B . CYAN . "  $m\n" . GRAY . "  " . str_repeat("─", 56)); }
function pstat(string $k, string $v): void { out(GRAY . "  " . B . str_pad($k, 22) . R . GRAY . $v); }

function og_ansi_len(string $s): int {
    return mb_strlen((string)preg_replace('/\033\[[0-9;]*m/', '', $s));
}

/** Рядок вкладок для TUI: активна — [назва], інші — сірі. */
function og_patch_cli_tab_bar(array $labels, int $active): string
{
    $chunks = [];
    foreach ($labels as $i => $lbl) {
        if ($i === $active) {
            $chunks[] = YEL . B . '[' . $lbl . ']' . R;
        } else {
            $chunks[] = GRAY . $lbl . R;
        }
    }
    return '  ' . implode(GRAY . '  ' . R, $chunks);
}

/**
 * Один рядок пункту меню: id, назва, підказка, прапорець CLI.
 *
 * @param array{id:string,title:string,hint:string,flag:string,c?:string} $it
 */
function og_patch_cli_menu_line(array $it, bool $selected, int $cols): void
{
    $id = str_pad((string)$it['id'], 2, ' ', STR_PAD_LEFT);
    $accent = (string)($it['c'] ?? CYAN);
    $title = (string)$it['title'];
    $hint = (string)$it['hint'];
    $flag = (string)$it['flag'];

    if ($selected) {
        $head = YEL . B . '> ' . $id . R . '  ' . $accent . B . $title . R;
    } else {
        $head = GRAY . '  ' . $id . R . '  ' . $accent . $title . R;
    }

    $flagPlain = $flag;
    $headVis = og_ansi_len('> ') + 3 + og_ansi_len($title);
    $room = $cols - 4;
    $line = $head;

    if ($hint !== '' && $headVis + 3 + og_ansi_len($hint) + 2 + og_ansi_len($flagPlain) <= $room) {
        $line .= GRAY . '  ' . $hint . R;
        $dots = max(1, $room - $headVis - og_ansi_len($hint) - og_ansi_len($flagPlain) - 4);
        $line .= ' ' . GRAY . str_repeat('.', $dots) . R . ' ' . CYAN . $flag . R;
    } elseif ($headVis + 2 + og_ansi_len($flagPlain) <= $room) {
        $dots = max(1, $room - $headVis - og_ansi_len($flagPlain) - 3);
        $line .= ' ' . GRAY . str_repeat('.', $dots) . R . ' ' . CYAN . $flag . R;
    } else {
        out('  ' . $line);
        $sub = GRAY . '      ' . $hint . R;
        if ($hint !== '' && og_ansi_len($hint) + og_ansi_len($flagPlain) + 8 <= $room) {
            $sub .= '  ' . CYAN . $flag . R;
        } else {
            $sub = CYAN . '      ' . $flag . R;
        }
        out('  ' . $sub);
        return;
    }

    out('  ' . $line);
}

/** Центрує рядок у терміналі з урахуванням ANSI. */
function og_patch_cli_center(string $text, int $cols, string $prefix = '', string $suffix = R): string
{
    $pad = max(0, (int)(($cols - og_ansi_len($text)) / 2));
    return str_repeat(' ', $pad) . $prefix . $text . $suffix;
}

/** Центральний фрагмент рядка, якщо він ширший за термінал. */
function og_patch_cli_crop_line_center(string $row, int $maxWidth): string
{
    if ($maxWidth < 8) {
        return $row;
    }
    $len = mb_strlen($row);
    if ($len <= $maxWidth) {
        return $row;
    }
    $start = (int)max(0, floor(($len - $maxWidth) / 2));
    return mb_substr($row, $start, $maxWidth);
}

/** Обрізає порожні braille-поля зліва/справа, залишає силует птиці. */
function og_patch_cli_trim_braille_art(array $lines): array
{
    $out = [];
    foreach ($lines as $line) {
        $line = rtrim((string)$line, "\r\n");
        $line = preg_replace('/^\x{2800}+/u', '', $line) ?? $line;
        $line = preg_replace('/\x{2800}+$/u', '', $line) ?? $line;
        if ($line !== '') {
            $out[] = $line;
        }
    }
    return $out;
}

/** Сирий Unicode-арт ястреба (вбудований у patch.php). */
function og_patch_cli_hawk_art_raw(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $raw = explode("\n", <<<'OG_HAWK_ART'
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣀⣠⣤⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣤⣄⣀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣠⣤⣶⣿⡿⢿⠛⣿⡇⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢸⣿⣿⣿⣿⣿⣶⣤⣄⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣤⣾⣿⣿⢟⢯⢣⡙⣌⠳⢼⡷⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣼⣿⣿⣿⣟⣿⣻⣿⣿⣿⣷⣤⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣠⣴⣿⣿⣟⢿⡸⣎⢎⢧⡙⢦⡙⣼⡟⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢼⣿⣿⣿⣾⢯⣷⣟⣾⣹⣟⣿⣿⣦⣄⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣲⣶⣿⣿⣿⣿⢻⣼⢣⡟⣜⢮⢲⢩⢦⣹⣿⠇⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠘⣿⣿⣿⣯⣿⢷⣻⣞⠷⣏⣶⢫⡿⣿⣿⣶⣖⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣠⣾⣿⢟⣽⣿⣟⣾⣿⢫⢷⡹⢎⣎⠳⢎⣶⣿⠋⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠙⣿⣿⣿⣯⣿⣳⢯⣟⠾⣽⣷⣯⡹⢿⣿⣿⣷⣄⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣠⣾⣿⢻⣼⣿⣿⣿⣿⣟⡾⣝⡮⣝⢧⢮⣽⣿⡿⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠈⢿⣿⣿⣷⣻⣟⢾⡻⣵⣚⣿⣿⣭⡿⣿⣽⢿⣷⣄⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣠⣾⣿⡟⡽⢮⣿⣿⣿⣿⣟⡾⣝⡮⢷⡹⣮⣾⡿⠋⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠙⢿⣿⣷⣯⢿⣹⢶⡹⣖⢻⣿⣿⣿⣿⢾⣹⢻⣷⣄⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣠⣶⣿⣿⣳⣏⢿⣿⠻⣵⣿⣟⡾⣽⡞⣽⣣⣿⡿⠋⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠙⢿⣿⣯⣗⢯⡳⣭⢇⡻⣿⣿⣿⣯⣏⡳⣝⢿⣶⣄⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣠⣴⣾⣿⣿⣿⣿⢯⢷⣽⣿⣋⣿⣿⣿⡽⣯⢷⣻⣾⣿⠋⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠙⣿⣿⣯⢶⣙⢮⡱⣛⣿⣯⢿⣷⣻⡜⣎⢿⣿⣿⣿⣶⣄⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣴⣿⣿⣿⣿⣛⣾⣿⢯⣟⣿⡟⡶⣽⣿⡿⣽⣻⣽⣾⢿⣿⠇⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠸⣿⣿⣿⣮⡳⢥⢳⡸⣿⣟⡮⢿⣞⡱⢎⢿⣿⣿⣿⣿⣿⣦⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣰⣿⣿⣿⣿⣟⢶⣿⣿⣿⣿⣿⢯⣟⣵⣿⣿⣻⣷⣿⠿⣱⣿⠏⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠹⣿⣿⣿⣿⣎⢧⡱⢻⣿⡽⣫⢻⣿⣎⠞⣿⣷⡿⣿⣿⣿⣿⣦⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣼⣿⣿⣿⣿⣟⢮⢿⣿⣿⣿⣿⣯⣟⣮⣿⣿⡿⣽⣿⣭⣓⣿⣿⡆⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢸⣿⣿⣿⡞⣿⣧⡳⡌⣿⡷⣏⢣⢿⣿⣷⣜⣿⡷⢯⣿⣿⣿⣿⣷⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣠⣾⣿⣿⣿⣿⣟⡞⣮⢿⣿⣿⣿⣿⣳⢯⢾⣿⣿⣿⣿⠷⣮⣽⡿⢹⡇⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣠⠤⠶⠒⠒⠲⠤⢤⣀⡀⠀⠀⠀⠀⠀⠀⠀⠀⠸⣿⢹⣿⣿⣱⡛⣷⡜⡼⣿⡝⡧⢎⢿⣿⣿⣿⣿⢳⡞⣿⣿⣿⣿⣿⣄⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣴⣿⣿⣿⣿⣿⣿⢯⡽⢮⣿⣿⣿⣿⣿⣽⣻⣿⣿⣿⣿⣯⢿⣱⣿⣧⢸⣷⣿⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣠⠖⠋⠉⠀⠀⠀⠀⠒⠲⣦⢤⣭⡷⡶⠤⣄⣀⠀⠀⣿⣾⣿⢀⣿⣷⣣⠯⡝⣿⡔⣿⣟⡼⣩⠞⣿⣿⣿⣯⣷⢺⠭⣿⣿⣿⣿⣿⣦⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⣠⣾⣿⣿⣿⣿⣿⣿⣯⠿⣽⣿⣏⣾⣿⣿⣿⢾⣽⣿⣿⣿⣿⣞⣧⣿⣟⢻⣾⡿⢻⣄⣴⡀⠀⠀⠀⠀⠀⠀⠀⢀⡴⢟⡶⠒⠉⠀⠀⠀⠀⠀⠀⠀⠈⠟⠉⣹⠃⠁⠀⠈⠙⣶⣿⢻⣿⣾⢻⣿⣷⢫⠝⡼⣿⣼⣿⡶⣡⠚⣽⣿⣿⣿⣯⣏⣞⡹⣿⣿⣿⣿⣿⣷⣤⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⣀⣴⣿⠿⠟⠋⣿⣿⣿⣿⣿⣽⣿⣿⢷⣸⣿⣿⣿⣯⣿⣿⣿⣿⣿⣷⣻⣾⣿⣃⠎⡹⣟⡘⣿⣿⣇⠀⠀⠀⠀⠀⢀⣴⣋⡴⣋⠄⢒⠞⠋⣁⣀⡀⠀⠀⠀⡤⠖⠛⢧⣄⣀⣀⡤⡄⢸⡟⣰⡟⣡⠒⣿⣿⡳⢎⡵⣿⣿⣿⣿⡵⢩⢸⣿⣿⣯⢿⣿⣆⢳⣹⣿⣿⣿⡙⠻⠿⣿⣦⣀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠉⠉⠀⠀⠀⢀⣾⣿⣿⣿⣿⣿⣾⣿⣟⡮⣵⣿⣿⣿⣿⣿⡻⡟⣿⣿⣯⣷⣿⡿⢿⣮⠁⠹⢷⣽⣇⠻⣆⣠⣤⣀⠐⠋⣽⣿⣿⠋⣰⣡⠖⠉⣴⠃⠔⠒⠉⠉⠉⡉⠉⣻⣶⡴⣶⣿⣿⣽⡷⠟⠰⣠⣿⣿⣿⣿⡘⡆⣿⣿⡿⣿⣿⣇⣹⣿⣿⡷⣫⢿⣿⣧⡚⣿⣿⣿⣷⡀⠀⠀⠈⠉⠉⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⣼⣿⣿⣿⣿⣿⡟⣿⣿⢯⡳⢞⣿⣿⣿⣿⣿⡱⡍⣿⣿⣷⣿⣿⣯⡟⣻⣿⣦⣠⠉⠹⠷⣾⣻⣧⣝⣻⣾⣿⣿⠃⣴⣏⣤⣶⣼⢁⣴⣶⡞⠀⣠⠀⢀⣼⣿⣿⣿⣿⡿⠟⠋⣐⣨⣶⣿⣿⣯⡿⣿⡜⡱⢾⣿⣟⢧⣿⣿⣾⣿⣿⣽⢣⣛⣿⣿⣿⣼⣿⣿⣿⣷⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⣼⣿⣿⣿⣿⣿⡟⡜⣿⣿⣯⣝⢺⣿⣿⢻⣿⣷⣣⢝⣿⣿⣿⣿⣿⣿⣼⡿⣏⣽⣿⢿⣶⣴⣠⠍⢭⣹⣿⣿⣿⣿⣾⣿⣿⣿⣿⢣⣾⣿⣿⣷⣾⣿⣅⣿⣿⣿⡉⡍⣡⣢⣭⣶⣿⣿⣿⣿⢿⣾⡽⣿⣷⠡⣿⣿⣟⡞⡼⣿⣿⣿⣿⣯⢳⡥⢿⣿⡽⣻⣿⣿⣿⣿⣧⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⢰⣿⣿⣿⣿⣿⣿⣹⣱⣿⣿⣞⢮⣿⣿⣋⢿⣿⣳⠞⣼⣿⣿⣿⣿⣿⣿⢯⣳⣾⣟⢧⣾⣿⡛⣿⣿⣷⣿⣿⣿⣿⣿⣿⣿⣿⣿⢇⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣷⣿⣿⣿⡿⣿⣿⣻⢿⣾⡏⡽⢿⣿⣿⢱⣿⣿⣯⡟⡴⣿⣿⠿⣿⣯⡗⣎⣹⣿⣽⢣⣿⣿⣿⣿⣿⡆⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⢀⣿⣿⣿⣿⣿⣿⣿⣣⣿⣿⣷⣯⣿⣿⡷⡭⢾⣿⣯⢽⣿⢧⣿⣿⣿⣿⣿⣿⣿⣟⣮⣿⡿⣧⣿⡿⣏⣶⡿⣿⣿⣿⣿⣯⣿⣿⣿⣾⣿⣿⣿⣿⣿⣿⣿⣿⣟⣿⣿⣿⣿⣻⢿⣽⡝⢿⣿⣮⣙⣿⣷⣿⣿⣿⣻⣿⣻⣿⣎⡕⣿⣿⢯⡽⣿⣿⣲⢸⣿⣯⡳⢾⣿⣿⣿⣿⣿⡀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⣸⣿⣿⣿⣿⣿⣿⣷⣿⣿⣿⣿⣿⣿⣿⠷⣍⢿⣿⣞⣿⡿⣇⢾⣿⣿⣿⣿⣽⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣾⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣷⣏⣿⣷⡘⣿⣿⡞⡴⢿⣿⣿⣾⣿⣿⣷⣹⣿⣿⣿⣿⣿⣇⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣽⣿⣿⢯⣓⢾⣿⣿⣿⣟⢦⢻⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣟⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣟⡶⣘⣿⣿⣿⣿⣜⢣⢻⣿⣿⣿⣿⣿⣿⣷⣿⣿⣿⣿⣿⣿⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⢠⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣳⣿⣿⣿⡻⡜⣾⣿⢏⣿⡿⣬⢻⣿⣿⣿⣿⣿⣿⣿⣿⣿⡿⠿⠿⠟⠻⠛⣩⡿⠛⠉⢩⣝⣿⣿⣿⣿⣿⣿⣿⣿⣿⣟⣘⠉⠙⢿⣍⠛⠟⠻⠿⠿⢿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣯⣗⢣⣿⡟⣿⣷⣏⢎⣻⣿⣿⣿⣿⢾⣿⣿⣿⣿⣿⣿⣿⣿⡇⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⢸⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣻⣿⣿⣿⢷⣹⣿⣟⢮⣿⡿⣔⣿⡟⠿⠛⠙⠛⠋⠈⢉⣥⣄⣀⠀⠀⠀⣼⣿⡵⢷⡶⢛⣿⣾⣿⣿⣿⣿⣿⣿⣿⣿⣾⢛⡳⣾⢶⣽⣧⠀⠀⠀⣀⣠⣬⡉⠀⠙⠛⠋⠛⠿⢻⣿⣷⢏⢶⣿⡭⢻⣿⣎⡎⣿⣿⣿⣿⣯⣟⣿⣿⣟⣿⣿⣿⣿⣿⡇⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⣾⣿⣿⠏⣽⣿⣿⣿⣿⣿⣿⣿⣿⣯⢿⣿⣿⣿⣿⣯⢧⢻⣟⢧⣿⡇⠀⠀⠀⠀⠀⠀⣿⢏⣞⣩⣛⡲⢾⣿⣿⣥⣼⡾⠿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⡿⠿⣶⣄⣻⣿⣷⠶⣛⡭⣹⡞⣿⠀⠀⠀⠀⠀⠀⢸⣿⣿⡏⣾⣿⡹⣹⣿⣿⡜⣿⣿⣿⣿⣷⢿⣾⣿⣿⣟⣧⠹⣿⣿⣷⠀⠀⠀⠀⠀
⠀⠀⠀⠀⣰⣿⡿⠃⠀⣿⣿⣿⣿⣿⣿⣿⣿⣿⣽⣻⣿⣿⣿⣿⣟⢮⢹⣿⣾⣿⡇⠀⠀⢀⣀⣀⣀⣿⣯⡯⠉⠋⠙⠳⣽⢿⣿⡿⣀⣉⡀⠛⣿⣿⣿⣿⣿⣿⣿⠟⢁⣀⡋⣹⣿⣿⢟⡵⠚⠙⡉⢹⣿⢿⣀⣀⣀⡀⠀⠀⢸⣿⣿⣷⣿⣯⠷⣩⣿⣿⣿⣿⣿⣿⣿⣿⣟⣾⣿⣿⣿⣿⠀⠘⢿⣿⣆⠀⠀⠀⠀
⠀⠀⠀⣰⣿⠟⠁⠀⠀⣿⣿⣿⣿⣿⣿⣿⣿⣿⢷⣿⣿⣿⢿⣿⣟⠮⣼⣿⡿⣿⡇⠀⠀⣼⡟⣠⠉⡛⣷⣿⠀⣦⠀⠀⠘⡟⣿⣷⡾⠉⠙⠻⣿⣿⣿⣿⣿⣿⣿⡾⠋⠉⢻⣶⣿⣏⡏⠀⠀⢠⡔⣸⣿⡟⠋⢡⡍⣷⠀⠀⢸⣿⣿⣿⣿⣯⡗⣹⣿⣿⣿⣿⣿⣿⡿⣿⣿⣞⣿⣿⣿⣿⠀⠀⠈⠻⣿⣆⠀⠀⠀
⠀⢀⣼⠿⠁⠀⠀⠀⣾⣿⣿⣿⣿⣿⣿⣿⣿⡿⣯⣿⣿⣿⢿⣿⣾⢫⢼⣿⡗⣿⡇⠀⠀⣿⣾⡿⠛⠶⠿⣿⣇⡈⠥⠀⢹⣧⣿⣿⣧⡀⠈⢳⣻⣿⣿⣿⣿⣿⣿⣱⠋⠀⣠⣿⣿⡖⣽⠀⠀⠋⣠⣿⡿⠶⠟⠻⣿⣿⠀⠀⢸⣿⣿⣣⣿⣷⢻⣼⣿⣿⣻⣿⣿⣿⣿⣽⣿⣿⣿⣿⣿⣿⣷⠀⠀⠀⠈⠻⣧⡀⠀
⡰⠟⠁⠀⠀⠀⠀⣸⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣟⣿⣿⣣⢾⣿⡇⣿⡇⠀⠀⣹⣿⡅⠠⣦⠀⠈⢻⣷⣄⠀⠈⢿⣿⣿⣿⣿⣄⠀⣿⣿⣿⣿⣿⣿⣿⡇⢀⣾⣿⣿⣿⣿⠇⠀⢀⣴⣿⠋⠀⢠⠆⠁⣾⣏⠀⠀⢸⣿⣿⣱⣿⣿⢳⣿⣿⣿⣷⣻⣿⣿⣾⡽⣿⣿⣿⣿⣿⣿⣿⣧⠀⠀⠀⠀⠈⠛⢆
⠀⠀⠀⠀⠀⠀⢀⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⡾⣽⣿⣧⢺⣿⣽⣿⡇⢀⣾⣩⣙⣿⣄⠈⠂⠀⠹⣿⣿⣷⣤⣀⠻⣿⣿⣿⣿⠀⣿⣿⢿⣿⢞⣿⣿⡇⢸⣿⣿⣿⡿⢁⣠⣴⣿⣿⡿⠁⠀⠋⣀⣴⣟⣩⣶⡀⢸⣿⣿⢧⣿⣿⣿⣿⣿⣿⣷⣻⣿⣿⣿⡽⣿⣿⣿⣿⣿⣿⣿⣿⡄⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⣸⣿⣿⣿⣿⣿⠏⢸⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣧⠘⢿⣏⢩⣉⠙⢻⣷⣄⠀⠙⢿⣿⣿⣿⣷⣾⣿⣿⣿⣼⣿⣿⣻⣿⣻⣿⣿⣿⣼⣿⣿⣷⣾⣿⣿⣿⣿⠟⠀⢀⣴⣟⠋⢉⣩⢉⡿⠁⣼⣿⢿⣿⣿⣿⣿⣿⣿⣿⣾⣽⣿⣿⣿⣟⣿⡇⠹⣿⣿⣿⣿⣿⣇⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⣿⣿⣿⣿⣿⠏⠀⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣻⣿⣿⣿⠀⠈⢿⣦⡙⠂⠈⠻⣿⣿⣦⣤⣙⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣽⣿⣳⣿⣯⢿⣿⣿⣿⣿⣿⣿⣿⣛⣡⣴⣾⣿⠿⠋⠀⠚⣡⡾⠃⠀⣾⣿⣿⣿⣿⣿⣟⣾⣿⣿⡷⣿⣿⣿⣿⣿⣿⣿⠀⢹⣿⣿⣿⣿⣿⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⣿⣟⣾⣿⡟⠀⠀⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⢾⣽⣿⣿⣿⡄⠀⠀⠈⠛⠶⣤⣀⡀⠙⠻⣿⣿⣿⣿⣿⣿⣿⣿⣿⡿⣿⣿⢾⣿⣳⣿⢧⣿⣿⣿⡼⣿⣿⣿⣿⣿⣿⠿⠛⠁⣀⣠⡴⠛⠁⠀⠀⢀⣿⣿⣿⣿⣿⣿⣟⣾⣿⣿⣿⣿⣿⢿⣿⣿⣿⣿⠀⠀⢻⣿⣿⣿⣿⡄⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⢸⣿⣯⣿⣿⠁⠀⢐⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣯⣿⣿⣿⣿⣧⠀⠀⠀⠀⠀⠀⠉⠉⠛⠛⠓⠻⣿⣿⣿⢿⣿⣿⣿⣽⣿⣿⣿⣿⣳⣿⡟⣾⣿⣿⣳⣻⣿⣿⣿⠟⠛⠛⠋⠉⠉⠀⠀⠀⠀⠀⠀⣸⣿⣿⣷⣿⣿⣿⣿⣿⡿⣽⣿⣿⣿⡿⣿⣿⣿⣿⡃⠀⠈⣿⣿⣿⣿⡇⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⢸⣿⢧⣿⠇⠀⠀⢨⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣳⣿⡄⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣼⣿⣿⣯⣟⣿⣿⡿⣞⣿⣿⣿⣿⣳⣿⣏⣿⣿⣿⣧⣟⣿⣿⣿⣧⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢠⣿⣿⣿⣿⣟⣿⣿⣿⣿⡽⣽⣿⣿⣿⣿⣿⣿⣿⣿⡇⠀⠀⠸⣿⣿⣿⡇⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠸⣿⣻⡿⠀⠀⠀⢸⣿⢾⣿⣿⣿⣿⣿⣽⣿⣿⣿⣯⢿⣿⣿⣿⣿⣿⣿⣷⠀⠀⠀⠀⠀⠀⠀⢀⣰⠟⠉⢉⣿⣷⡿⠿⢻⠿⠿⠟⢹⠏⣹⠉⣿⠛⠿⠿⢿⠿⠿⣿⣿⣏⠉⠙⢦⡀⠀⠀⠀⠀⠀⠀⠀⣾⣿⣿⣿⣿⣿⣹⣿⣿⣯⣟⣿⣿⣿⣿⣷⣿⣿⣿⣿⡇⠀⠀⠀⢿⣿⣿⡇⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⣿⣿⡇⠀⠀⠀⠸⣿⣻⣿⣿⣿⣿⣿⣻⣿⣿⣿⣿⣻⣿⣿⣿⣿⣿⣿⣿⣧⠀⠀⠀⠀⠀⠀⠈⠻⣶⣶⡿⠁⠀⠀⣰⡏⠀⢀⣰⡿⠴⠋⠶⠿⣧⣀⠀⠘⣧⡀⠀⠀⠹⣶⣶⠿⠃⠀⠀⠀⠀⠀⠀⣸⣿⣿⣿⣿⣿⣷⣻⣿⣿⣿⣽⣿⣿⣿⣿⣿⣿⣿⣿⣿⡇⠀⠀⠀⢸⣿⣿⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⣿⣿⠃⠀⠀⠀⠘⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣷⣟⣿⣿⣿⣽⣿⢿⣿⣿⣧⠀⠀⠀⠀⠀⠀⢀⣰⣟⣁⣤⡴⠞⠻⠿⠟⠋⠁⠀⠀⠀⠀⠀⠈⠙⠻⠿⠟⠻⢶⣤⣤⣙⣇⡀⠀⠀⠀⠀⠀⠀⣰⣿⣿⣿⣿⣿⣿⣷⣿⣿⣿⣟⣾⣿⣿⣿⣿⣿⣿⣿⣿⣿⡅⠀⠀⠀⠘⣿⣿⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⢻⣿⠀⠀⠀⠀⢈⣿⣿⣿⣿⣿⡏⢹⣿⣿⣿⣿⣿⣞⣿⣿⣿⣽⣿⣞⣿⣿⣿⣷⣀⠀⠀⠀⠀⠈⠉⠉⠉⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠈⠉⠉⠁⠀⠀⠀⠀⢀⣾⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⡏⢹⣿⣿⣿⣿⣿⠄⠀⠀⠀⠀⣿⡿⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⢸⣿⠀⠀⠀⠀⠀⣿⣿⣿⣿⣿⠁⠈⣿⣿⣿⣿⣿⣾⣿⣿⣿⣿⣿⣷⣻⣿⡌⠻⢿⣦⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣴⣿⠟⢩⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⢿⣿⣿⠁⠀⣿⣿⣿⣿⣿⠀⠀⠀⠀⠀⣿⡇⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⣿⠀⠀⠀⠀⠀⢻⣿⣿⣿⡇⠀⠀⢹⣿⣿⣿⣿⣟⣾⢿⣿⣿⣿⣿⣿⣿⣿⣄⠀⠉⠛⠳⠄⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠠⠖⠛⠉⠀⢠⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⡏⠀⠀⢸⣿⣿⣿⡟⠀⠀⠀⠀⠀⣿⠁⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⢛⠀⠀⠀⠀⠀⢸⣿⣿⣿⠀⠀⠀⠈⣿⣿⣿⣿⣧⡿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣆⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣰⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣽⣿⠁⠀⠀⠀⢿⣿⣿⡏⠀⠀⠀⠀⠀⠛⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠈⣿⣿⡇⠀⠀⠀⠀⢹⣿⣿⣿⣷⣿⢿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣧⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣴⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⣿⡏⠀⠀⠀⠀⢸⣿⣿⠁⠀⠀⠀⠀⠀⠁⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢿⣿⡇⠀⠀⠀⠀⠈⣿⣿⣿⣿⣿⣿⣿⣿⠈⢿⣿⣿⣿⣿⣿⣿⣷⡄⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣾⣿⣿⣿⣿⣿⣿⣿⠃⣿⣿⣿⣿⣿⣿⣿⣿⠃⠀⠀⠀⠀⢸⣿⡿⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠸⣿⡇⠀⠀⠀⠀⠀⢿⣿⠃⢻⣿⣿⣽⣿⡇⠈⢿⣿⣿⣿⣿⠻⢿⣿⣦⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣴⣿⣿⠟⣿⣿⣿⣿⡿⠁⢰⣿⣿⣿⣿⡿⠘⣿⡿⠀⠀⠀⠀⠀⢸⣿⡇⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣿⡇⠀⠀⠀⠀⠀⢸⡿⠀⠈⣿⣿⣿⣿⣇⠀⠈⢻⣿⣿⣿⣷⡈⠙⢿⣿⣦⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣴⣿⡿⠋⢀⣾⣿⣿⣿⡟⠁⠀⣸⣿⣿⣿⣿⠁⠀⢿⡇⠀⠀⠀⠀⠀⢸⣿⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢸⣧⠀⠀⠀⠀⠀⢸⡇⠀⠀⠘⣿⣿⣿⣿⠀⠀⠀⠙⣿⣿⣿⣷⡀⠀⠉⠻⢿⣦⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣴⡿⠟⠉⠀⢀⣾⣿⣿⣿⠏⠀⠀⠀⣿⣿⣿⣿⠃⠀⠀⢸⡇⠀⠀⠀⠀⠀⢸⡇⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢿⠀⠀⠀⠀⠀⢸⠀⠀⠀⠀⠘⣿⣿⣿⡇⠀⠀⠀⠈⠻⣿⣿⣷⡄⠀⠀⠀⠈⠛⠶⣄⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣠⠴⠛⠁⠀⠀⠀⢠⣾⣿⣿⠟⠁⠀⠀⠀⢰⣿⣿⣿⠃⠀⠀⠀⠀⡇⠀⠀⠀⠀⠀⡿⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠘⡆⠀⠀⠀⠀⠘⠀⠀⠀⠀⠀⠘⢿⣿⣿⡀⠀⠀⠀⠀⠈⠻⣿⣿⣆⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣰⣿⣿⠟⠁⠀⠀⠀⠀⠀⣾⣿⡿⠃⠀⠀⠀⠀⠀⠛⠀⠀⠀⠀⠠⠃⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠈⠻⣿⣧⡀⠀⠀⠀⠀⠀⠈⠛⢿⣷⡄⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣾⡿⠟⠁⠀⠀⠀⠀⠀⠀⣼⣿⡟⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠙⢿⣧⠀⠀⠀⠀⠀⠀⠀⠀⠙⠻⣦⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣴⠟⠋⠀⠀⠀⠀⠀⠀⠀⠀⣼⡿⠋⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠹⣷⡀⠀⠀⠀⠀⠀⠀⠀⠀⠈⠙⠦⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠴⠋⠁⠀⠀⠀⠀⠀⠀⠀⠀⢀⣾⠟⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠈⠹⢦⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⣠⠟⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠙⠂⡀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⢀⣠⠊⠁⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀⠀
OG_HAWK_ART);
    $cache = [];
    foreach ($raw as $line) {
        $line = rtrim((string)$line, "\r\n");
        if ($line !== '' || $cache !== []) {
            $cache[] = $line;
        }
    }
    while ($cache !== [] && $cache[count($cache) - 1] === '') {
        array_pop($cache);
    }

    return $cache;
}

/** Верхня половина рядків Unicode-арту (без зміни символів у рядку). */
function og_patch_cli_hawk_top_half(array $lines): array
{
    if ($lines === []) {
        return [];
    }

    return array_slice($lines, 0, (int)ceil(count($lines) / 2));
}

/** RGB → найближчий xterm-256 (куб 16–231 + грейскейл). */
function og_patch_cli_rgb_to_256(int $r, int $g, int $b): int
{
    $r = max(0, min(255, $r));
    $g = max(0, min(255, $g));
    $b = max(0, min(255, $b));
    if ($r === $g && $g === $b) {
        if ($r < 8) {
            return 16;
        }
        if ($r > 248) {
            return 231;
        }
        return 232 + (int)round($r / 10.65);
    }
    return 16 + 36 * (int)round($r / 51) + 6 * (int)round($g / 51) + (int)round($b / 51);
}

/** Щільність символу (braille — за кількістю точок). */
function og_patch_cli_hawk_char_weight(string $ch): float
{
    if ($ch === '' || $ch === ' ') {
        return 0.0;
    }
    $o = function_exists('mb_ord') ? mb_ord($ch) : ord($ch);
    if ($o === 0x2800 || $ch === '⠀') {
        return 0.0;
    }
    if ($o >= 0x2800 && $o <= 0x28FF) {
        $dots = substr_count(decbin($o - 0x2800), '1');

        return 0.08 + ($dots / 8.0) * 0.92;
    }
    if ($o >= 0x2580 && $o <= 0x259F) {
        return 1.0;
    }
    if ($o >= 0x2500 && $o <= 0x257F) {
        return 0.88;
    }

    return 0.75;
}

/** Чи це «чорнило» силуету (будь-який непорожній символ, не braille-пробіл). */
function og_patch_cli_hawk_is_ink(float $weight): bool
{
    return $weight > 0.0;
}

/** Фіксовані xterm-256 для лого (без жовтих/коричневих відтінків від rgb_to_256). */
const OG_PATCH_CLI_HAWK_ORANGE_256 = 208;
/** Бордовий (#870000). */
const OG_PATCH_CLI_HAWK_RED_256 = 88;

/** Фон: braille-пробіл → звичайний пробіл (без сірого «плями» в терміналі). */
function og_patch_cli_hawk_bg_char(string $ch): string
{
    $o = function_exists('mb_ord') ? mb_ord($ch) : ord($ch);

    return ($o === 0x2800 || $ch === '⠀') ? ' ' : $ch;
}

/**
 * Колір клітини: none = фон, orange = щільне тіло, red = усе інше на силуеті.
 *
 * @return 'none'|'orange'|'red'
 */
function og_patch_cli_hawk_cell_paint_role(
    bool $isHole,
    float $weight,
    array $silMask,
    int $y,
    int $x
): string {
    if (!og_patch_cli_hawk_is_ink($weight) && !$isHole) {
        return 'none';
    }
    if ($isHole) {
        return 'red';
    }
    if (og_patch_cli_hawk_cell_is_interior($silMask, $y, $x)) {
        return 'orange';
    }

    return 'red';
}

/**
 * Маска силуету: усі клітини з «чорнилом» (без braille-пробілів).
 *
 * @param list<list<array{ch:string,wt:float}>> $grid
 * @return list<list<bool>>
 */
function og_patch_cli_hawk_ink_mask(array $grid): array
{
    $mask = [];
    foreach ($grid as $y => $row) {
        $mask[$y] = [];
        foreach ($row as $x => $cell) {
            $mask[$y][$x] = og_patch_cli_hawk_is_ink($cell['wt']);
        }
    }

    return $mask;
}

/**
 * «Дірки» всередині силуету (⠀, оточені чорнилом) — напр. голова між крилами.
 *
 * @param list<list<bool>> $inkMask
 * @return list<list<bool>>
 */
function og_patch_cli_hawk_hole_mask(array $inkMask): array
{
    $h = count($inkMask);
    if ($h === 0) {
        return [];
    }

    $w = 0;
    foreach ($inkMask as $row) {
        $w = max($w, count($row));
    }
    if ($w === 0) {
        return [];
    }

    $isEmpty = static function (array $mask, int $y, int $x) use ($h, $w): bool {
        if ($y < 0 || $y >= $h || $x < 0 || $x >= $w) {
            return true;
        }
        if ($x >= count($mask[$y])) {
            return true;
        }

        return !($mask[$y][$x] ?? false);
    };

    $outside = array_fill(0, $h, array_fill(0, $w, false));
    $queue = [];
    for ($y = 0; $y < $h; $y++) {
        $rowW = count($inkMask[$y]);
        for ($x = 0; $x < $w; $x++) {
            if (!$isEmpty($inkMask, $y, $x)) {
                continue;
            }
            if ($y === 0 || $y === $h - 1 || $x === 0 || $x === $w - 1 || $x >= $rowW) {
                $outside[$y][$x] = true;
                $queue[] = [$y, $x];
            }
        }
    }

    while ($queue !== []) {
        [$y, $x] = array_shift($queue);
        foreach ([[0, 1], [0, -1], [1, 0], [-1, 0]] as [$dy, $dx]) {
            $ny = $y + $dy;
            $nx = $x + $dx;
            if ($ny < 0 || $ny >= $h || $nx < 0 || $nx >= $w || $outside[$ny][$nx] || !$isEmpty($inkMask, $ny, $nx)) {
                continue;
            }
            $outside[$ny][$nx] = true;
            $queue[] = [$ny, $nx];
        }
    }

    $holes = [];
    for ($y = 0; $y < $h; $y++) {
        $rowW = count($inkMask[$y]);
        $holes[$y] = [];
        for ($x = 0; $x < $w; $x++) {
            $holes[$y][$x] = $x < $rowW && $isEmpty($inkMask, $y, $x) && !($outside[$y][$x] ?? false);
        }
    }

    return $holes;
}

/** Маска силуету: чорнило + внутрішні дірки (для заливки). */
function og_patch_cli_hawk_silhouette_mask(array $inkMask, array $holeMask): array
{
    $sil = [];
    foreach ($inkMask as $y => $row) {
        $sil[$y] = [];
        foreach ($row as $x => $ink) {
            $sil[$y][$x] = $ink || ($holeMask[$y][$x] ?? false);
        }
    }

    return $sil;
}

/** Внутрішність: чорнило, оточене чорнилом з усіх 8 боків (щільне тіло). */
function og_patch_cli_hawk_cell_is_interior(array $mask, int $y, int $x): bool
{
    if (!($mask[$y][$x] ?? false)) {
        return false;
    }
    $h = count($mask);
    $w = count($mask[0] ?? []);
    for ($dy = -1; $dy <= 1; $dy++) {
        for ($dx = -1; $dx <= 1; $dx++) {
            if ($dy === 0 && $dx === 0) {
                continue;
            }
            $ny = $y + $dy;
            $nx = $x + $dx;
            if ($ny < 0 || $ny >= $h || $nx < 0 || $nx >= $w || !($mask[$ny][$nx] ?? false)) {
                return false;
            }
        }
    }

    return true;
}

/**
 * Рендер ястреба: як у сирому арті (braille), фон без кольору, тіло — помаранчеве, контур — червоний.
 *
 * @param list<string> $bird
 * @return list<string>
 */
function og_patch_cli_render_hawk(array $bird): array
{
    $h = count($bird);
    if ($h === 0) {
        return [];
    }

    $grid = [];
    foreach ($bird as $y => $row) {
        $row = (string)$row;
        $grid[$y] = [];
        $len = mb_strlen($row);
        for ($x = 0; $x < $len; $x++) {
            $ch = mb_substr($row, $x, 1);
            $grid[$y][$x] = ['ch' => $ch, 'wt' => og_patch_cli_hawk_char_weight($ch)];
        }
    }

    $inkMask = og_patch_cli_hawk_ink_mask($grid);
    $holeMask = og_patch_cli_hawk_hole_mask($inkMask);
    $silMask = og_patch_cli_hawk_silhouette_mask($inkMask, $holeMask);

    $lines = [];
    foreach ($bird as $y => $row) {
        $out = '';
        $len = mb_strlen((string)$row);
        $styled = false;
        for ($x = 0; $x < $len; $x++) {
            $cell = $grid[$y][$x];
            $ch = $cell['ch'];
            $wt = $cell['wt'];
            $isHole = ($holeMask[$y][$x] ?? false) && !og_patch_cli_hawk_is_ink($wt);

            // Справжній фон (не всередині силуету).
            if (!og_patch_cli_hawk_is_ink($wt) && !$isHole) {
                if ($styled) {
                    $out .= R;
                    $styled = false;
                }
                $out .= og_patch_cli_hawk_bg_char($ch);
                continue;
            }

            if ($isHole) {
                $ch = '⣿';
            }

            $role = og_patch_cli_hawk_cell_paint_role($isHole, $wt, $silMask, $y, $x);
            if ($role === 'orange') {
                $out .= R . "\033[38;5;" . OG_PATCH_CLI_HAWK_ORANGE_256 . 'm' . $ch;
            } else {
                $out .= R . "\033[38;5;" . OG_PATCH_CLI_HAWK_RED_256 . 'm' . B . $ch;
            }
            $styled = true;
        }
        if ($styled) {
            $out .= R;
        }
        $lines[] = $out;
    }

    return $lines;
}

/**
 * Лого TUI: Unicode-ястреб (block art) + OFFERGUARD.
 *
 * @return list<string> ANSI-рядки
 */
function og_patch_cli_logo_lines(int $cols, int $rows = 40): array
{
    $bird = og_patch_cli_hawk_top_half(og_patch_cli_hawk_art_raw());

    if ($cols < 36) {
        return [
            og_patch_cli_center(RED . B . 'OFFERGUARD' . R, $cols),
            og_patch_cli_center('live-token · anti-mirror · bot-kill', $cols, GRAY, R),
        ];
    }

    $lines = [];
    // Без обрізки/trim — повний арт; центрування лише зовнішніми пробілами.
    foreach (og_patch_cli_render_hawk($bird) as $colored) {
        $lines[] = og_patch_cli_center($colored, max($cols, og_ansi_len($colored)));
    }

    $lines[] = '';
    $lines[] = og_patch_cli_center(RED . B . 'OFFERGUARD' . R, $cols);

    $tag = 'live-token  ·  anti-mirror  ·  bot-kill';
    $tagStyled = GRAY . $tag . R;
    $lines[] = og_patch_cli_center($tagStyled, $cols);

    return $lines;
}

/** Друкує лого + опційний рядок статусу. */
function og_patch_cli_print_logo(int $cols, ?string $statusLine = null, int $rows = 40): void
{
    foreach (og_patch_cli_logo_lines($cols, $rows) as $ln) {
        out($ln);
    }
    if ($statusLine !== null && $statusLine !== '') {
        out(og_patch_cli_center($statusLine, $cols, GRAY, R));
    }
}

if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool
    {
        if ($needle === '') return true;
        $len = strlen($needle);
        return $len <= strlen($haystack) && substr($haystack, -$len) === $needle;
    }
}


function og_remaining(int $ts): string
{
    $s = max(0, $ts - time());
    if ($s < 60)   return $s . 'с';
    if ($s < 3600) return floor($s / 60) . 'м ' . ($s % 60) . 'с';
    return floor($s / 3600) . 'ч ' . floor(($s % 3600) / 60) . 'м';
}

if ($argc < 2 || in_array($argv[1], ['-h', '--help'])) {
    og_patch_print_help();
    exit(0);
}

function og_patch_print_help(): void
{
    [$helpRows, $helpCols] = og_patch_cli_term_size();
    og_patch_cli_print_logo(max(52, $helpCols), null, $helpRows);
    out(B . "\n  OfferGuard Patcher v6 — защита оффера на любом стеке\n");
    out(GRAY . "  PHP, Node/Next, Python/Django, Ruby, Java, .NET, Go, статика, mixed.");
    out(GRAY . "  OgFramework — внутренний движок детекта; граница продукта — OfferGuard.\n");

    out(B . GRAY . "  ━━━ АКТИВИРОВАТЬ ЗАЩИТУ (одна команда, без флагов) ━━━");
    out("");
    out(B . "    php patch.php " . YEL . "/путь/к/html-outlet");
    out("");
    out(GRAY . "    HTML outlet: public_html, out/, dist/, templates/ (см. OFFERGUARD_STACKS.md).");
    out(GRAY . "    Включает: патч HTML/шаблонов, bot-protect.php, JS/crypto, kill копий.");
    out(GRAY . "    PHP html-via-php (до 5 файлів) — runtime inject; иначе HTML + bot-protect.");
    out(GRAY . "    Домен из пути (.../" . YEL . "домен.com/public_html" . GRAY . ") или --canonical-host=.");
    out("");

    out(B . GRAY . "  ━━━ ПРИМЕРЫ КОРНЯ ПАТЧА ━━━");
    out("");
    out(CYAN . "    php patch.php " . YEL . "/repo/public_html          " . R . "# PHP/Laravel/WordPress");
    out(CYAN . "    php patch.php " . YEL . "/repo/out                  " . R . "# Next static export");
    out(CYAN . "    php patch.php " . YEL . "/repo/templates            " . R . "# Django/Flask HTML");
    out(CYAN . "    php patch.php " . YEL . "/repo                      " . R . "# авто: ищет outlet");
    out("");

    out(B . GRAY . "  ━━━ ПРОВЕРИТЬ ━━━");
    out("");
    out(CYAN . "    php patch.php " . YEL . "/путь " . CYAN . "--verify-copy   " . R . "# умрёт ли копия (МЕРТВА/ЖИВА)");
    out(CYAN . "    php patch.php " . YEL . "/путь " . CYAN . "--status        " . R . "# что включено + баны");
    out(CYAN . "    php patch.php " . YEL . "/путь " . CYAN . "--rollback      " . R . "# откатить из _og_backup");
    out(CYAN . "    php patch.php " . YEL . "/путь " . CYAN . "--cleanup       " . R . "# снять следы OfferGuard (безопасно)");
    out(CYAN . "    php patch.php " . YEL . "/путь " . CYAN . "--scan-deep     " . R . "# глубже скан (больше лимиты)");
    out("");
    out(GRAY . "    Проверка копии: открой сохранённый файл / на чужом хосте / file://");
    out(GRAY . "    → должно быть пусто. На своём домене в браузере — работает.");
    out("");

    out(B . GRAY . "  ━━━ ЕСЛИ ДОМЕН НЕ В ПУТИ ━━━");
    out("");
    out(CYAN . "    php patch.php " . YEL . "/путь " . CYAN . "--canonical-host=ваш-домен.com");
    out(GRAY . "    (нужно, только если папка НЕ вида .../домен/public_html;");
    out(GRAY . "     в интерактивном терминале патчер спросит домен сам)");
    out("");

    out(B . GRAY . "  ━━━ ОБСЛУЖИВАНИЕ ━━━");
    out("");
    out(CYAN . "    --bans / --whitelist            " . R . "# списки IP");
    out(CYAN . "    --unban|--allow|--deny 1.2.3.4  " . R . "# unban: знімає ban-state (НЕ whitelist'ит — щоб JS-гейт працював); --allow: явно у whitelist (тільки для тестів); --deny: прибрати з whitelist");
    out(CYAN . "    --why 1.2.3.4                   " . R . "# повна історія IP: бани, причини, гейти, час");
    out(CYAN . "    --watch                         " . R . "# live tail логів bot-protect (Ctrl+C для виходу)");
    out(CYAN . "    --doctor                        " . R . "# діагностика + auto-fix прав/false-positive банів");
    out(CYAN . "    --site-up                       " . R . "# секрет _og_data, chmod, htaccess, hotfix");
    out(CYAN . "    --recovery                      " . R . "# АВАРІЙНЕ: зачистити всі OG залишки коли сайт не вантажиться");
    out(CYAN . "    --reset-state                   " . R . "# обнулити bot-protect state (бани, suspect, fingerprints, логи)");
    out(CYAN . "    --traffic [--tail|--class bot]  " . R . "# трафик");
    out(CYAN . "    --sessions                      " . R . "# сессии");
    out(CYAN . "    --xlog                          " . R . "# детальный xlog (последние 50 строк): live-gate, decrypt, assets — серверные + клиентские JS-ошибки");
    out(CYAN . "    --xlog-tail [--xlog-level=warn] " . R . "# live tail xlog с фильтром по уровню (debug|info|warn|error|fatal)");
    out(CYAN . "    --xlog-tail --xlog-filter=fn=X  " . R . "# фильтр по подстроке (например fn=_ogDecryptPayload)");
    out(CYAN . "    --xlog-clear                    " . R . "# очистить og_xlog.log");
    out(CYAN . "    --dry-run                       " . R . "# прогон без записи");
    out("");

    out(B . GRAY . "  ━━━ ЭКСПЕРТНОЕ (обычно НЕ нужно) ━━━");
    out("");
    out(GRAY . "    --minimal              отключить шифр (только guard) — для боевого НЕ надо");
    out(GRAY . "    --og-encrypt-body=0    --og-encrypt-js=0    --og-key-split=0");
    out(GRAY . "    --og-encrypt-php=0     --og-assets-subdir=имя    --og-assets-as=js|png|webp");
    out(GRAY . "    --og-htaccess=1        писать .htaccess з /_site/* clean URLs (за замовч. ВИМК — pure PHP)");
    out(GRAY . "    --og-php-inject=1      PHP inject в index (по умолчанию ВЫКЛ если есть HTML)");
    out(GRAY . "    --og-origin-safe       origin: без head kill (document.write), copy через server");
    out(GRAY . "    --og-emergency-unbreak снять OfferGuard-правила из .htaccess (сайт UP)");
    out(GRAY . "    Всё перечисленное по умолчанию УЖЕ включено по максимуму.");
    out("");

    out(B . GRAY . "  ━━━ КАК ЭТО РАБОТАЕТ ━━━");
    out("");
    out(GRAY . "    Любой стек:  последний HTML в браузере патчится; runtime — bot-protect.php");
    out(GRAY . "                 (Apache /_site/* или nginx proxy, см. OFFERGUARD_STACKS.md).");
    out(GRAY . "    Свой домен:  live-токен + decrypt → сайт работает.");
    out(GRAY . "    Копия:       нет токена с origin → пустая страница / reject.");
    out(GRAY . "    API-only без HTML: патчер пишет og_runtime_embed_instructions.md.");
    out("");
}

$cliArgvPath = (string)$argv[1];
$offerPath = rtrim(realpath($cliArgvPath) ?: $cliArgvPath, '/');
$cliPathExplicit = og_patch_cli_path_is_explicit($cliArgvPath);
$args      = array_slice($argv, 2);
$autoProtect = !in_array('--no-auto-protect', $args, true)
    && !in_array('--minimal', $args, true);
if (in_array('--no-auto-protect', $args, true) && in_array('--minimal', $args, true)) {
    warn('--minimal і --no-auto-protect — один режим; використовується мінімальний патч.');
}

$dryRun    = in_array('--dry-run',   $args);
$GLOBALS['OG_PATCH_SCAN_DEEP'] = in_array('--scan-deep', $args, true);

og_patch_env_check();
function og_patch_env_check(): void
{
    $phpV = PHP_VERSION;
    $phpOk = version_compare($phpV, '7.4.0', '>=');
    $mods = [
        'openssl'  => ['need' => 'crit', 'why' => 'AES-шифрування контенту'],
        'curl'     => ['need' => 'opt',  'why' => 'webhook + live-fetch'],
        'mbstring' => ['need' => 'opt',  'why' => 'безпечні UTF-операції'],
        'json'     => ['need' => 'crit', 'why' => 'токени/конфіг'],
        'hash'     => ['need' => 'crit', 'why' => 'HMAC ключі'],
        'fileinfo' => ['need' => 'opt',  'why' => 'визначення MIME'],
        'dom'      => ['need' => 'opt',  'why' => 'строге парсення HTML (fallback regex)'],
    ];
    $crit = [];
    $miss = [];
    foreach ($mods as $m => $meta) {
        if (!extension_loaded($m)) {
            $label = $m . ' (' . $meta['why'] . ')';
            if ($meta['need'] === 'crit') {
                $crit[] = $label;
            } else {
                $miss[] = $label;
            }
        }
    }
    $dnsFn = function_exists('dns_get_record') ? 'yes' : 'no';
    $sslFn = function_exists('openssl_encrypt') ? 'yes' : 'no';
    if (!$phpOk || $crit) {
        warn('[env] PHP ' . $phpV . ($phpOk ? '' : ' — потрібно ≥ 7.4'));
        if ($crit) { warn('[env] критичні модулі відсутні: ' . implode(', ', $crit) . ' — захист буде ослаблено'); }
    }
    if ($miss && (in_array('--verbose', $GLOBALS['argv'] ?? [], true) || in_array('--env', $GLOBALS['argv'] ?? [], true))) {
        info('[env] опційно: ' . implode(', ', $miss));
    }
    if (!empty($GLOBALS['OG_PATCH_SCAN_DEEP']) || in_array('--env', $GLOBALS['argv'] ?? [], true)) {
        info('[env] PHP ' . $phpV . ' | openssl_encrypt=' . $sslFn . ' dns_get_record=' . $dnsFn
            . ' | модулі: ' . implode(',', get_loaded_extensions()));
    }
}
$ogRollbackParse = og_patch_parse_rollback_from_args($args);
$rollback  = $ogRollbackParse['rollback'];
if (!empty($ogRollbackParse['typo_note'])) {
    warn($ogRollbackParse['typo_note']);
}
og_patch_validate_unknown_cli_flags($args);
$showStatus= in_array('--status',    $args);
$cleanup   = in_array('--cleanup',   $args);
$verifyCopy= in_array('--verify-copy', $args);
$showBans  = in_array('--bans',      $args);
$showWl    = in_array('--whitelist', $args);
$doctor    = in_array('--doctor',    $args);
$siteUp    = in_array('--site-up',   $args);

$showTraffic = in_array('--traffic',   $args);
$showSessions= in_array('--sessions',  $args);
$tailMode    = in_array('--tail',      $args);   

$unbanIp = $allowIp = $denyIp = null;
$filterIp    = null;
$filterClass = null;
$limitLines  = 50;
$whyIp       = null;
$watchMode   = in_array('--watch', $args, true);
$recoveryMode = in_array('--recovery', $args, true);
$resetStateMode = in_array('--reset-state', $args, true);
$verifyMode = in_array('--verify', $args, true);
$xlogMode = in_array('--xlog', $args, true);
$xlogTailMode = in_array('--xlog-tail', $args, true);
$xlogClearMode = in_array('--xlog-clear', $args, true);
$xlogLevel = null;
$xlogFilter = null;
foreach ($args as $_xi => $_xa) {
    if (strpos($_xa, '--xlog-level=') === 0) $xlogLevel = strtolower(trim(substr($_xa, 13)));
    if (strpos($_xa, '--xlog-filter=') === 0) $xlogFilter = trim(substr($_xa, 14));
}
foreach ($args as $i => $a) {
    if ($a === '--unban' && isset($args[$i + 1])) $unbanIp = trim($args[$i + 1]);
    if ($a === '--allow' && isset($args[$i + 1])) $allowIp = trim($args[$i + 1]);
    if ($a === '--deny'  && isset($args[$i + 1])) $denyIp  = trim($args[$i + 1]);
    if ($a === '--why'   && isset($args[$i + 1])) $whyIp   = trim($args[$i + 1]);
    if ($a === '--ip'    && isset($args[$i + 1])) $filterIp    = trim($args[$i + 1]);
    if ($a === '--class' && isset($args[$i + 1])) $filterClass = trim($args[$i + 1]);
    if ($a === '--limit' && isset($args[$i + 1])) $limitLines  = (int)$args[$i + 1];
}

$ogAssetsSubdir   = '_og_assets';
$ogAssetsSubdirExplicit = false;
$ogAssetsAs       = 'js';
$ogAssetExt       = null;
$ogAssetsAsExplicit = false;
$ogAssetsHtaccess = false;
$ogAssetsNocache  = false;
$ogAssetsHtaccessExplicit = false;
$ogAssetsNocacheExplicit  = false;
$ogEncryptJs      = false;
$ogEncryptJsExplicit = false;
$ogEncryptBody    = false;
$ogEncryptBodyExplicit = false;
$ogKeySplit       = false;
$ogKeySplitExplicit = false;
$ogEncryptPhp     = false;
$ogEncryptPhpExplicit = false;
$ogAggressiveMax  = false;
$ogWebhookMode    = 'notify';
$ogWriteHtaccess  = false;
$ogWriteHtaccessExplicit = false;
$ogPhpInject      = null;
$ogPhpInjectExplicit = false;
$ogEmergencyUnbreak = in_array('--og-emergency-unbreak', $args, true);
$ogOriginSafe = in_array('--og-origin-safe', $args, true);
foreach ($args as $a) {
    if (!is_string($a)) {
        continue;
    }
    if (str_starts_with($a, '--og-assets-subdir=')) {
        $raw = trim(substr($a, strlen('--og-assets-subdir=')));
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]{0,62}$/', $raw)) {
            $ogAssetsSubdir = $raw;
            $ogAssetsSubdirExplicit = true;
        } else {
            warn('Некоректний --og-assets-subdir= — лишаємо _og_assets');
        }
        continue;
    }
    if (str_starts_with($a, '--og-assets-as=')) {
        $ogAssetsAsExplicit = true;
        $raw = strtolower(trim(substr($a, strlen('--og-assets-as='))));
        if (in_array($raw, ['js', 'png', 'webp'], true)) {
            $ogAssetsAs = $raw;
        } else {
            warn('Некоректний --og-assets-as= (js|png|webp) — лишаємо js');
        }
        continue;
    }
    if (str_starts_with($a, '--og-asset-ext=')) {
        $raw = trim(substr($a, strlen('--og-asset-ext=')));
        if ($raw !== '' && $raw[0] !== '.') {
            $raw = '.' . $raw;
        }
        if (preg_match('/^\.[A-Za-z0-9]{1,12}$/', $raw)) {
            $ogAssetExt = strtolower($raw);
        } else {
            warn('Некоректний --og-asset-ext= — ігноруємо');
        }
        continue;
    }
    if (str_starts_with($a, '--og-assets-htaccess=')) {
        $ogAssetsHtaccessExplicit = true;
        $ogAssetsHtaccess = trim(substr($a, strlen('--og-assets-htaccess='))) === '1';
        continue;
    }
    if (str_starts_with($a, '--og-assets-nocache=')) {
        $ogAssetsNocacheExplicit = true;
        $ogAssetsNocache = trim(substr($a, strlen('--og-assets-nocache='))) === '1';
        continue;
    }
    if (str_starts_with($a, '--og-encrypt-js=')) {
        $ogEncryptJsExplicit = true;
        $ogEncryptJs = trim(substr($a, strlen('--og-encrypt-js='))) === '1';
        continue;
    }
    if (str_starts_with($a, '--og-encrypt-body=')) {
        $ogEncryptBodyExplicit = true;
        $ogEncryptBody = trim(substr($a, strlen('--og-encrypt-body='))) === '1';
        continue;
    }
    if (str_starts_with($a, '--og-webhook-mode=')) {
        $raw = strtolower(trim(substr($a, strlen('--og-webhook-mode='))));
        if (in_array($raw, ['notify', 'authoritative'], true)) {
            $ogWebhookMode = $raw;
        } else {
            warn('Некоректний --og-webhook-mode= (notify|authoritative) — лишаємо notify');
        }
        continue;
    }
    if (str_starts_with($a, '--og-key-split=')) {
        $ogKeySplitExplicit = true;
        $ogKeySplit = trim(substr($a, strlen('--og-key-split='))) === '1';
        continue;
    }
    if (str_starts_with($a, '--og-encrypt-php=')) {
        $ogEncryptPhpExplicit = true;
        $ogEncryptPhp = trim(substr($a, strlen('--og-encrypt-php='))) === '1';
        continue;
    }
    if ($a === '--og-no-htaccess' || str_starts_with($a, '--og-no-htaccess=')) {
        $ogWriteHtaccessExplicit = true;
        $ogWriteHtaccess = false;
        continue;
    }
    if (str_starts_with($a, '--og-htaccess=')) {
        $ogWriteHtaccessExplicit = true;
        $ogWriteHtaccess = trim(substr($a, strlen('--og-htaccess='))) === '1';
        continue;
    }
    if (str_starts_with($a, '--og-php-inject=')) {
        $ogPhpInjectExplicit = true;
        $ogPhpInject = trim(substr($a, strlen('--og-php-inject='))) === '1';
        continue;
    }
    if (str_starts_with($a, '--og-htaccess-safe=')) {
        $GLOBALS['OG_PATCH_HTACCESS_SAFE'] = trim(substr($a, strlen('--og-htaccess-safe='))) !== '0';
        continue;
    }
    if (str_starts_with($a, '--og-htaccess-full=')) {
        $GLOBALS['OG_PATCH_HTACCESS_SAFE'] = trim(substr($a, strlen('--og-htaccess-full='))) !== '1';
        $ogWriteHtaccessExplicit = true;
        $ogWriteHtaccess = true;
        continue;
    }
    if (str_starts_with($a, '--og-aggressive=')) {
        $raw = strtolower(trim(substr($a, strlen('--og-aggressive='))));
        if ($raw === 'max') {
            $ogAggressiveMax = true;
            if (!$ogKeySplitExplicit) {
                $ogKeySplit = true;
            }
            if (!$ogEncryptBodyExplicit) {
                $ogEncryptBody = true;
            }
            if (!$ogEncryptPhpExplicit) {
                $ogEncryptPhp = true;
            }
        } else {
            warn('Некоректний --og-aggressive= (max) — ігноруємо');
        }
        continue;
    }
}
$ogWebhookUrlPatch = '';
$ogWhEnvPatch = getenv('OG_WEBHOOK_URL');
if (is_string($ogWhEnvPatch) && trim($ogWhEnvPatch) !== '') {
    $ogWebhookUrlPatch = trim($ogWhEnvPatch);
}

if (!is_dir($offerPath)) { fail("Папка не найдена: $offerPath"); exit(1); }

$ogPatchResolveMeta = [];
$ogSkipAutoPromote = og_patch_cli_should_skip_auto_promote($args);
[$offerPath, $ogPatchResolveMeta] = og_patch_resolve_patch_directory(
    $offerPath,
    $cliPathExplicit || og_patch_is_generic_web_dir(basename($offerPath)),
    $ogSkipAutoPromote
);
if (!empty($ogPatchResolveMeta['changed'])) {
    info('[OfferGuard] patch root: ' . $offerPath
        . ' (було: ' . ($ogPatchResolveMeta['input'] ?? '?') . ', ' . ($ogPatchResolveMeta['reason'] ?? 'auto') . ')');
}
if (!empty($ogPatchResolveMeta['notes'])) {
    foreach ($ogPatchResolveMeta['notes'] as $ogPn) {
        info('[OfferGuard] ' . $ogPn);
    }
}
if (!og_patch_is_generic_web_dir(basename($offerPath))) {
    warn('[OfferGuard] patch root не public_html/www/htdocs — переконайтесь, що це document root (не батьківська папка домену)');
}

// AUTO-FIX: убираем «бродячие» OfferGuard-инсталляции в родительских каталогах.
// Случай: ранее юзер пропатчил parent dir (например `/home/test/web/basincx.info/`)
// по ошибке, теперь патчит правильный webroot. Без чистки два bot-protect и
// два разных .og_secret конфликтуют → blank page.
$ogStrayCleanupSkip = $rollback || $cleanup || $showStatus || $verifyCopy
    || $showBans || $showWl || $showTraffic || $showSessions
    || $unbanIp !== null || $allowIp !== null || $denyIp !== null
    || $ogEmergencyUnbreak
    || $xlogMode || $xlogTailMode || $xlogClearMode
    || $watchMode || $siteUp || $whyIp !== null || $doctor;
if (!$ogStrayCleanupSkip) {
    $ogStrayParent = dirname($offerPath);
    if ($ogStrayParent !== $offerPath && is_dir($ogStrayParent)) {
        $ogStrayBp = $ogStrayParent . '/bot-protect.php';
        $ogStrayDd = $ogStrayParent . '/_og_data';
        $ogStrayIsOg = is_file($ogStrayBp) && og_patch_is_bot_protect_file($ogStrayBp);
        if ($ogStrayIsOg || is_dir($ogStrayDd)) {
            head('AUTO-FIX — бродяча інсталяція в parent');
            warn('Знайдено OfferGuard у ' . $ogStrayParent . ' — це НЕ webroot. Очищаю автоматично.');
            info('Цей parent НЕ обслуговується Apache; залишити обидві установки = blank page.');
            $ogStrayBackup = $ogStrayParent . '/_og_backup';
            // Откатываем parent установку (восстанавливает HTML, сносит bot-protect и т.д.)
            og_patch_rollback($ogStrayParent, $ogStrayBackup, $dryRun, false, true);
            // Также удаляем сам _og_backup в parent — он там лишний.
            if (!$dryRun && is_dir($ogStrayBackup)) {
                if (og_patch_rmdir_recursive($ogStrayBackup)) {
                    ok('Удалена папка: _og_backup/ (parent)');
                }
            }
            // Удалим лишние patch.php.save* которые могли остаться от sed/vim.
            foreach (glob($ogStrayParent . '/patch.php.save*') ?: [] as $sav) {
                if (!$dryRun && @unlink($sav)) {
                    ok('Удалён мусор: ' . basename($sav));
                }
            }
            info('Готово. Продовжую патч у ' . $offerPath);
        }
    }
}

if ($ogEmergencyUnbreak) {
    head('EMERGENCY UNBREAK — .htaccess');
    $htEmergency = $offerPath . '/.htaccess';
    if (og_patch_emergency_htaccess_disable($htEmergency, $dryRun)) {
        ok(($dryRun ? '[DRY] ' : '') . 'OfferGuard-правила знято: ' . $htEmergency);
        info('Далі: php patch.php ' . $offerPath . ' --rollback  (index.php / HTML)');
        info('Безпечний re-patch: php patch.php ' . $offerPath . ' --canonical-host=basincx.info');
    } else {
        warn('Немає .htaccess або OfferGuard-блоків у ' . $offerPath);
    }
    exit(0);
}

$ogWizTty = false;
if (function_exists('stream_isatty')) { $ogWizTty = @stream_isatty(STDIN); }
elseif (function_exists('posix_isatty')) { $ogWizTty = @posix_isatty(STDIN); }
$ogInteractiveMode = false;
$ogInteractiveNeedMenu = false;
$ogInteractivePauseBeforeMenu = false;
$ogAsk = static function (string $q, string $def = '') use (&$ogInteractiveMode): string {
    fwrite(STDOUT, "\n  " . $q . ($def !== '' ? " [" . $def . "]" : '') . ": ");
    $v = fgets(STDIN);
    $v = $v === false ? '' : trim($v);
    if ($ogInteractiveMode && empty($GLOBALS['ogMenuTuiActive'])) {
        og_patch_cli_clear_screen();
    }
    return $v === '' ? $def : $v;
};
if ($ogWizTty && count($args) === 0
    && !$rollback && !$cleanup && !$showStatus && !$verifyCopy && !$showBans && !$showWl
    && !$showTraffic && !$showSessions
    && $unbanIp === null && $allowIp === null && $denyIp === null) {
    $ogInteractiveMode = true;
    $GLOBALS['ogPermBootstrap'] = og_patch_fix_og_data_writable($offerPath, og_patch_resolve_web_owner($offerPath));
    $ogInteractiveNeedMenu = true;
}


function og_pick_cloak_subdir(string $offerPath, bool $createIfMissing = true): string
{
    $candidates = ['assets', 'static', 'media', 'dist', 'build', 'vendor', 'chunks', 'lib', 'cache'];
    foreach ($candidates as $cand) {
        $p = $offerPath . '/' . $cand;
        if (is_dir($p) && is_file($p . '/.og_cloak')) {
            return $cand;
        }
    }
    foreach (@glob($offerPath . '/*', GLOB_ONLYDIR) ?: [] as $d) {
        if (is_file($d . '/.og_cloak')) {
            return basename($d);
        }
    }
    if (!$createIfMissing) {
        // Read-only режимы (--status/--rollback/--cleanup/...) НЕ создают каталог,
        // иначе trace появляется после каждой read-only команды.
        return 'assets';
    }
    foreach ($candidates as $cand) {
        if (!file_exists($offerPath . '/' . $cand)) {
            @mkdir($offerPath . '/' . $cand, 0755, true);
            @file_put_contents($offerPath . '/' . $cand . '/.og_cloak', "ogcloak\n", LOCK_EX);
            return $cand;
        }
    }
    do {
        $rand = substr(bin2hex(random_bytes(5)), 0, 8);
    } while (file_exists($offerPath . '/' . $rand));
    @mkdir($offerPath . '/' . $rand, 0755, true);
    @file_put_contents($offerPath . '/' . $rand . '/.og_cloak', "ogcloak\n", LOCK_EX);

    return $rand;
}

if (!$ogAssetsSubdirExplicit) {
    $ogReadOnlyMode = $rollback || $showStatus || $cleanup || $verifyCopy || $showBans || $showWl
        || !empty($showTraffic) || !empty($showSessions)
        || !empty($doctor) || !empty($whyIp) || !empty($watchMode) || !empty($recoveryMode);
    $ogAssetsSubdir = og_pick_cloak_subdir($offerPath, !$ogReadOnlyMode);
}

$backupDir   = $offerPath . '/_og_backup';
$protectDest = $offerPath . '/bot-protect.php';
$dataDir     = $offerPath . '/_og_data';

/**
 * Подбирает (uid, gid) под которыми работает Apache, по существующим файлам
 * сайта. Нужно когда патчер запущен как root, а Apache — www-data: без chown
 * bot-protect.php при попытке записать в _og_data получает permission denied
 * и сразу падает → blank page.
 *
 * Возвращает [uid, gid] или null если не root / не Linux / нечего сравнивать.
 */
function og_patch_detect_web_owner(string $offerPath): ?array
{
    if (!function_exists('posix_geteuid') || posix_geteuid() !== 0) {
        return null;
    }
    foreach (['index.php', 'index.html', 'index.htm'] as $idx) {
        $p = $offerPath . '/' . $idx;
        if (is_file($p)) {
            $st = @stat($p);
            if ($st && isset($st['uid'], $st['gid']) && $st['uid'] !== 0) {
                return [(int)$st['uid'], (int)$st['gid']];
            }
        }
    }
    // Fallback: владелец самого webroot'а.
    $st = @stat($offerPath);
    if ($st && isset($st['uid'], $st['gid']) && $st['uid'] !== 0) {
        return [(int)$st['uid'], (int)$st['gid']];
    }
    foreach (['www-data', 'apache', 'nginx', 'http', 'nobody'] as $u) {
        if (!function_exists('posix_getpwnam')) {
            break;
        }
        $pw = @posix_getpwnam($u);
        if (is_array($pw) && isset($pw['uid'], $pw['gid'])) {
            return [(int)$pw['uid'], (int)$pw['gid']];
        }
    }
    return null;
}

/** Власник для _og_data: webroot → поточний CLI-юзер. */
function og_patch_resolve_web_owner(string $offerPath): ?array
{
    $o = og_patch_detect_web_owner($offerPath);
    if ($o !== null) {
        return $o;
    }
    foreach (['index.php', 'index.html', 'index.htm'] as $idx) {
        $p = rtrim($offerPath, '/') . '/' . $idx;
        if (!is_file($p)) {
            continue;
        }
        $st = @stat($p);
        if ($st && isset($st['uid'], $st['gid']) && (int)$st['uid'] !== 0) {
            return [(int)$st['uid'], (int)$st['gid']];
        }
    }
    $st = @stat(rtrim($offerPath, '/'));
    if ($st && isset($st['uid'], $st['gid']) && (int)$st['uid'] !== 0) {
        return [(int)$st['uid'], (int)$st['gid']];
    }
    if (function_exists('posix_geteuid')) {
        return [posix_geteuid(), posix_getegid()];
    }
    return null;
}

/**
 * Применяет (uid, gid) к файлу/папке. Тихо игнорирует если нет прав.
 */
function og_patch_chown_recursive(string $path, int $uid, int $gid): void
{
    @chown($path, $uid);
    @chgrp($path, $gid);
    if (is_dir($path)) {
        foreach (@scandir($path) ?: [] as $e) {
            if ($e === '.' || $e === '..') continue;
            og_patch_chown_recursive($path . '/' . $e, $uid, $gid);
        }
    }
}

function og_patch_ensure_og_data_dir(string $offerPath, ?array $owner = null): array
{
    $offerPath = rtrim($offerPath, '/');
    $dataDir = $offerPath . '/_og_data';
    $created = false;
    if (!is_dir($dataDir)) {
        if (!@mkdir($dataDir, 0755, true)) {
            return ['path' => $dataDir, 'ok' => false, 'created' => false];
        }
        $created = true;
    }
    @chmod($dataDir, 0775);
    if (!is_file($dataDir . '/.htaccess')) {
        @file_put_contents($dataDir . '/.htaccess', "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n");
    }
    if (!is_file($dataDir . '/index.php')) {
        @file_put_contents($dataDir . '/index.php', '<?php // silence');
    }
    if ($owner !== null) {
        og_patch_chown_recursive($dataDir, $owner[0], $owner[1]);
    }
    return ['path' => $dataDir, 'ok' => is_dir($dataDir) && is_writable($dataDir), 'created' => $created];
}

function og_patch_ensure_webroot_secret(string $offerPath, ?array $owner = null, ?string $protectDest = null): array
{
    $offerPath = rtrim($offerPath, '/');
    $dataDir = $offerPath . '/_og_data';
    $inTree = $dataDir . '/.og_secret';
    $parentDir = dirname(realpath($offerPath) ?: $offerPath);
    $parentSecret = ($parentDir !== '' && $parentDir !== $offerPath) ? $parentDir . '/.og_secret' : '';
    if (!is_dir($dataDir)) {
        @mkdir($dataDir, 0755, true);
        @file_put_contents($dataDir . '/index.php', '<?php // silence');
    }
    @chmod($dataDir, 0755);
    $copied = false;
    $created = false;
    if (is_file($parentSecret) && @filesize($parentSecret) >= 32) {
        if (!is_file($inTree) || @filesize($inTree) < 32 || @file_get_contents($parentSecret) !== @file_get_contents($inTree)) {
            $copied = @copy($parentSecret, $inTree);
        }
    }
    if (!is_file($inTree) || @filesize($inTree) < 32) {
        $bytes = (is_file($parentSecret) && @filesize($parentSecret) >= 32) ? (string)@file_get_contents($parentSecret) : bin2hex(random_bytes(48));
        @file_put_contents($inTree, $bytes, LOCK_EX);
        $created = true;
    }
    @chmod($inTree, 0644);
    if ($owner !== null) {
        og_patch_chown_recursive($dataDir, $owner[0], $owner[1]);
        og_patch_chown_recursive($inTree, $owner[0], $owner[1]);
    }
    $migratedBp = false;
    if ($protectDest !== null && is_file($protectDest) && is_file($inTree) && $parentSecret !== '' && str_contains((string)@file_get_contents($protectDest), $parentSecret)) {
        @file_put_contents($protectDest, str_replace($parentSecret, $inTree, (string)@file_get_contents($protectDest)), LOCK_EX);
        $migratedBp = true;
    }
    return ['path' => $inTree, 'parent' => $parentSecret, 'ok' => is_file($inTree) && @filesize($inTree) >= 32, 'copied' => $copied, 'created' => $created, 'migrated_bp' => $migratedBp];
}

function og_patch_fix_og_data_writable(string $offerPath, ?array $owner = null): array
{
    $notes = [];
    $offerPath = rtrim($offerPath, '/');
    if ($owner === null) {
        $owner = og_patch_resolve_web_owner($offerPath);
    }
    $boot = og_patch_ensure_og_data_dir($offerPath, $owner);
    $dataDir = $boot['path'];
    $protectDest = $offerPath . '/bot-protect.php';
    if (!is_dir($dataDir)) {
        return ['path' => $dataDir, 'fixed' => false, 'notes' => ['missing']];
    }

    $isRoot = function_exists('posix_geteuid') && posix_geteuid() === 0;
    if ($owner !== null && $isRoot) {
        og_patch_chown_recursive($dataDir, $owner[0], $owner[1]);
        if (is_file($protectDest)) {
            @chown($protectDest, $owner[0]);
            @chgrp($protectDest, $owner[1]);
            @chmod($protectDest, 0644);
        }
        $notes[] = 'chown → uid ' . $owner[0] . ':' . $owner[1];
    }

    @chmod($dataDir, 0775);
    $logNames = ['og_access.log', 'og_runtime.log', 'og_blocked.log', 'og_traffic.log', 'og_sessions.log', 'og_xlog.log'];
    foreach ($logNames as $lf) {
        $fp = $dataDir . '/' . $lf;
        if (!is_file($fp)) {
            @file_put_contents($fp, '', LOCK_EX);
        }
        @chmod($fp, 0664);
        if ($owner !== null && $isRoot) {
            @chown($fp, $owner[0]);
            @chgrp($fp, $owner[1]);
        }
    }

    foreach (@glob($dataDir . '/*') ?: [] as $fp) {
        if (!is_file($fp)) {
            continue;
        }
        @chmod($fp, 0664);
        if ($owner !== null && $isRoot) {
            @chown($fp, $owner[0]);
            @chgrp($fp, $owner[1]);
        }
    }

    $test = $dataDir . '/.og_wt_' . getmypid();
    $ok = @file_put_contents($test, '1', LOCK_EX) !== false;
    if ($ok) {
        @unlink($test);
    } else {
        @chmod($dataDir, 0777);
        $ok = @file_put_contents($test, '1', LOCK_EX) !== false;
        if ($ok) {
            @unlink($test);
            $notes[] = 'chmod 0777 _og_data (fallback)';
        }
    }

    $ipTest = $dataDir . '/' . md5('127.0.0.1') . '.json';
    $jOk = @file_put_contents($ipTest, '{"ip":"127.0.0.1"}', LOCK_EX) !== false;
    if ($jOk) {
        @chmod($ipTest, 0664);
        if ($owner !== null && $isRoot) {
            @chown($ipTest, $owner[0]);
            @chgrp($ipTest, $owner[1]);
        }
        @unlink($ipTest);
    } else {
        $ok = false;
    }

    if ($ok && $notes === []) {
        $notes[] = 'chmod 0775 _og_data';
    }

    return ['path' => $dataDir, 'fixed' => $ok, 'notes' => $notes, 'owner' => $owner];
}

function og_clear_ip_ban_state(string $dataDir, string $ip): array
{
    og_patch_ensure_og_data_dir(dirname($dataDir), null);
    if (!is_dir($dataDir)) @mkdir($dataDir, 0755, true);
    $ipf = $dataDir . '/' . md5($ip) . '.json';
    $changed = ['state' => false, 'perm' => false];

    $old = is_file($ipf) ? (json_decode((string)@file_get_contents($ipf), true) ?: []) : [];
    $data = ['ip' => $ip];
    $changed['state'] = $old !== $data;
    file_put_contents($ipf, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
    @chmod($ipf, 0664);

    $permFile = $dataDir . '/perm_ban.txt';
    if (is_file($permFile)) {
        $list = file($permFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $new = array_values(array_filter(array_map('trim', $list), static fn($row) => $row !== $ip));
        if (count($new) < count($list)) {
            file_put_contents($permFile, $new ? implode("\n", $new) . "\n" : '', LOCK_EX);
            $changed['perm'] = true;
        }
    }

    return $changed;
}

function og_clear_ip_ban_state_all(string $offerPath, string $dataDir, string $ip): array
{
    $total = og_clear_ip_ban_state($dataDir, $ip);
    $seen = [realpath($dataDir) ?: $dataDir => true];

    try {
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($offerPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iter as $f) {
            if (!$f->isDir() || $f->getFilename() !== '_og_data') continue;
            $dir = $f->getPathname();
            $key = realpath($dir) ?: $dir;
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $changed = og_clear_ip_ban_state($dir, $ip);
            $total['state'] = $total['state'] || $changed['state'];
            $total['perm']  = $total['perm']  || $changed['perm'];
        }
    } catch (Throwable $e) {
        
    }

    return $total;
}

function og_find_og_data_dirs(string $offerPath, ?string $seedDir = null): array
{
    $dirs = [];
    $seen = [];
    $add = static function (string $dir) use (&$dirs, &$seen): void {
        $key = realpath($dir) ?: $dir;
        if (isset($seen[$key])) return;
        $seen[$key] = true;
        $dirs[] = $dir;
    };

    if ($seedDir && is_dir($seedDir)) {
        $add($seedDir);
    }
    $root = rtrim($offerPath, '/') . '/_og_data';
    if (is_dir($root)) {
        $add($root);
    }
    try {
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($offerPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iter as $f) {
            if ($f->isDir() && $f->getFilename() === '_og_data') {
                $add($f->getPathname());
            }
        }
    } catch (Throwable $e) {}

    sort($dirs);
    return $dirs;
}

function og_last_ban_reason_for_ip(string $rtLog, string $ip): ?string
{
    if (!is_file($rtLog)) return null;
    $lines = file($rtLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    for ($i = count($lines) - 1; $i >= 0; $i--) {
        $l = $lines[$i];
        if (strpos($l, '|BAN|type=') === false) continue;
        $p = explode('|', $l);
        $lip = $p[1] ?? '';
        if ($lip !== $ip) continue;
        $ts = $p[0] ?? '-';
        $uri = $p[2] ?? '-';
        $meta = implode('|', array_slice($p, 4));
        return $ts . " " . $meta . " uri=" . $uri;
    }
    return null;
}


function og_patch_cli_show_logs(string $offerPath, int $lines = 100): void
{
    head('ЛОГИ — bot-protect');
    $dataDir = $offerPath . '/_og_data';
    $logFiles = [
        'og_access.log'   => 'КОЖЕН запит — code, method, ip, ep, reason (unconditional)',
        'og_runtime.log'  => 'runtime події (бани, гейти, помилки)',
        'og_blocked.log'  => 'детальний journal 403/блокувань',
        'og_traffic.log'  => 'трафік (тільки для непереривних 200-x)',
        'og_sessions.log' => 'завершення сесій',
    ];

    // 1) Знайти ВСІ існуючі логи + показати їх стан
    $foundAny = false;
    foreach ($logFiles as $name => $desc) {
        $f = $dataDir . '/' . $name;
        $exists = is_file($f);
        $size = $exists ? filesize($f) : 0;
        if ($exists) {
            $foundAny = true;
            pstat($name, '✓ ' . round($size / 1024, 1) . ' KB — ' . $desc);
        } else {
            pstat($name, '✗ нема — ' . $desc);
        }
    }
    out('');

    if (!$foundAny) {
        warn('Жодного лог-файлу не знайдено в ' . $dataDir);
        info('Це означає одне з:');
        info('  1) bot-protect.php ще не отримав жодного HTTP-запиту → відкрий сайт у браузері');
        info('  2) bot-protect.php не може писати в _og_data (права) → запусти `--doctor`');
        info('  3) Apache не викликає bot-protect.php → перевір .htaccess i URL');
        info('Швидкий тест: curl -sI https://<host>/bot-protect.php?_og_ep=v');
        out('');
        return;
    }

    // 2) Показати tail кожного існуючого логу
    foreach ($logFiles as $name => $desc) {
        $f = $dataDir . '/' . $name;
        if (!is_file($f) || filesize($f) === 0) continue;
        out(CYAN . "  ─── $name ───");
        $all = file($f, FILE_IGNORE_NEW_LINES) ?: [];
        $tail = array_slice($all, -max(1, $lines));
        foreach ($tail as $line) {
            $col = GRAY;
            if (strpos($line, '|BAN') !== false || strpos($line, 'BAN|') !== false) {
                $col = RED;
            } elseif (strpos($line, '|WARN') !== false || strpos($line, '403') !== false) {
                $col = YEL;
            }
            out($col . '  ' . substr(rtrim($line), 0, 220));
        }
        out(GRAY . '  (показано ' . count($tail) . ' з ' . count($all) . ')');
        out('');
    }
}


function og_patch_cli_show_bans(string $offerPath, string $dataDir): void
{
    head('ЗАБАНЕННЫЕ IP');
    // Ищем все возможные _og_data: основной + parent (если устаревший install).
    $dataDirs = [];
    if (is_dir($dataDir)) $dataDirs[$dataDir] = true;
    $parentDd = dirname($offerPath) . '/_og_data';
    if (is_dir($parentDd) && rtrim($parentDd, '/') !== rtrim($dataDir, '/')) {
        $dataDirs[$parentDd] = true;
        info("Знайдено застарілу _og_data в parent: $parentDd — додано до пошуку.");
    }
    if ($dataDirs === []) {
        warn('_og_data не найдена — bot-protect.php ще НЕ оброблял жодного запиту.');
        info('Це означає: або Apache не викликає bot-protect, або сайт ніколи не відкривався.');
        info('Перевір: curl -sI https://<host>/bot-protect.php?_og_ep=v');
        out('');
        return;
    }
    $now  = time();
    $bans = [];
    foreach (array_keys($dataDirs) as $dd) {
        foreach (glob($dd . '/*.json') ?: [] as $f) {
            if (!preg_match('/^[a-f0-9]{32}\.json$/', basename($f))) continue;
            $data = json_decode(@file_get_contents($f), true) ?? [];
            if (empty($data['ip'])) continue;
            $atk = (!empty($data['atk_block']) && $data['atk_block'] > $now) ? $data['atk_block'] : 0;
            $rl  = (!empty($data['rl_block'])  && $data['rl_block']  > $now) ? $data['rl_block']  : 0;
            if (!$atk && !$rl) continue;
            $bans[] = [
                'ip'    => $data['ip'],
                'type'  => $atk ? 'permanent' : 'rate-limit',
                'until' => $atk ?: $rl,
                'reason'=> (string)($data['ban_reason'] ?? '-'),
                'source'=> (string)($data['ban_source'] ?? '-'),
                'dir'   => $dd,
            ];
        }
    }
    foreach (array_keys($dataDirs) as $dd) {
        $permFile = $dd . '/perm_ban.txt';
        if (!is_file($permFile)) continue;
        foreach (file($permFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $ip) {
            $ip = trim($ip);
            if (!$ip) continue;
            $bans[] = [
                'ip'    => $ip,
                'type'  => 'perm-list',
                'until' => $now + 315360000,
                'reason'=> 'perm_ban',
                'source'=> 'perm_ban.txt',
                'dir'   => $dd,
            ];
        }
    }
    if (empty($bans)) {
        ok('немає активних банів');
        // Якщо тобі здається що тебе банить — це ймовірно front-end JS-гард,
        // а не bot-protect. Підказку дамо.
        $dataFiles = 0;
        foreach (array_keys($dataDirs) as $dd) {
            $dataFiles += count(glob($dd . '/*.json') ?: []);
        }
        if ($dataFiles === 0) {
            info('У _og_data немає жодного ip-state файлу. Якщо сайт не вантажиться — це front-end blanker, не ban.');
            info('Запусти: php patch.php ' . $offerPath . ' --doctor');
        }
    } else {
        usort($bans, fn($a, $b) => strcmp($a['type'], $b['type']) ?: strcmp($a['ip'], $b['ip']));
        out(GRAY . '  ' . B . str_pad('IP', 20) . str_pad('Тип', 13) . str_pad('Осталось', 12) . str_pad('До', 21) . 'Причина');
        out(GRAY . '  ' . str_repeat('─', 66));
        // Виявляємо IP саму CLI-команди (можливо то твій IP заблоковано).
        $selfIp = '';
        $sshConn = getenv('SSH_CONNECTION');
        if (is_string($sshConn) && trim($sshConn) !== '') {
            $parts = preg_split('/\s+/', trim($sshConn));
            if (count($parts) >= 1) $selfIp = $parts[0];
        }
        foreach ($bans as $b) {
            $col = $b['type'] === 'permanent' ? RED : YEL;
            $isSelf = $selfIp !== '' && $b['ip'] === $selfIp;
            out($col . '  ' . str_pad($b['ip'] . ($isSelf ? ' ⚠ ТВІЙ' : ''), 26)
                . str_pad($b['type'], 13)
                . str_pad(og_remaining($b['until']), 12)
                . GRAY . str_pad(date('Y-m-d H:i:s', $b['until']), 21)
                . ($b['reason'] ?? '-'));
        }
        out(GRAY . "\n  Всего: " . YEL . count($bans) . GRAY . ' бан(ов)');
        out(GRAY . "  Разбанить: php patch.php $offerPath --unban <IP>");
        if ($selfIp !== '') {
            $hasSelfBan = false;
            foreach ($bans as $b) if ($b['ip'] === $selfIp) { $hasSelfBan = true; break; }
            if ($hasSelfBan) {
                out(YEL . "  ⚠ Твій IP $selfIp у бані. Зняти: php patch.php $offerPath --unban $selfIp");
                out(YEL . "       або: php patch.php $offerPath --allow $selfIp (додати у вайтлист)");
            }
        }
    }
    out('');
}


/**
 * Повний звіт по конкретному IP: ip-state JSON, історія банів з логів,
 * whitelist/permban статус, поточні гейти. Допомагає зрозуміти ПОЧЕМУ
 * IP отримує 403 і як його остаточно розблокувати.
 */
function og_patch_cli_why_ip(string $offerPath, string $dataDir, string $ip): void
{
    head("WHY — повна історія IP: $ip");
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        fail("Невалідний IP: $ip"); out(''); return;
    }

    // 1) Поточний ip-state JSON
    $ipf = $dataDir . '/' . md5($ip) . '.json';
    if (is_file($ipf)) {
        $state = json_decode((string)@file_get_contents($ipf), true) ?: [];
        $now = time();
        pstat('ip-state file', $ipf);
        $atk = (int)($state['atk_block'] ?? 0);
        $rl  = (int)($state['rl_block']  ?? 0);
        pstat('atk_block', $atk > $now
            ? RED . date('Y-m-d H:i:s', $atk) . ' (' . og_remaining($atk) . ')' . GRAY
            : (GREEN . 'нет' . GRAY));
        pstat('rl_block', $rl > $now
            ? YEL . date('Y-m-d H:i:s', $rl) . ' (' . og_remaining($rl) . ')' . GRAY
            : (GREEN . 'нет' . GRAY));
        pstat('ban_reason', (string)($state['ban_reason'] ?? '-'));
        pstat('ban_source', (string)($state['ban_source'] ?? '-'));
        pstat('suspect', (string)($state['suspect'] ?? '0'));
        if (!empty($state['suspect_reasons']) && is_array($state['suspect_reasons'])) {
            pstat('suspect_reasons', implode(', ', array_slice($state['suspect_reasons'], -10)));
        }
        pstat('rl_strikes', (string)($state['rl_strikes'] ?? '0'));
        pstat('strikes_total', (string)($state['strikes_total'] ?? '0'));
        pstat('timing_strikes', (string)($state['timing_strikes'] ?? '0'));
        pstat('timing_std', (string)($state['timing_std'] ?? '-'));
        pstat('human_score', (string)($state['human_score'] ?? '-'));
        if (!empty($state['ts']) && is_array($state['ts'])) {
            pstat('req count (last 1h)', (string)count($state['ts']));
            $tsRecent = array_slice($state['ts'], -5);
            $recentTimes = array_map(fn($t) => date('H:i:s', (int)$t), $tsRecent);
            pstat('last requests', implode(' ', $recentTimes));
        }
        if (!empty($state['last_violation'])) {
            pstat('last violation', date('Y-m-d H:i:s', (int)$state['last_violation']));
        }
    } else {
        warn('ip-state файл не існує: bot-protect жодного разу не бачив цей IP.');
    }

    // 2) Whitelist?
    $wlFile = $dataDir . '/whitelist.txt';
    $isWl = false;
    if (is_file($wlFile)) {
        $list = file($wlFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $isWl = in_array(trim($ip), array_map('trim', $list), true);
    }
    pstat('У вайтлисті', $isWl ? GREEN . 'ТАК' . GRAY : 'нет');

    // 3) Perm-ban?
    $permFile = $dataDir . '/perm_ban.txt';
    $isPerm = false;
    if (is_file($permFile)) {
        $list = file($permFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $isPerm = in_array(trim($ip), array_map('trim', $list), true);
    }
    pstat('У perm-bani', $isPerm ? RED . 'ТАК' . GRAY : 'нет');

    // 4) Історія з runtime-логу (всі дії з цим IP)
    out(CYAN . "\n  Історія з og_runtime.log:");
    $rtLog = $dataDir . '/og_runtime.log';
    if (is_file($rtLog)) {
        $lines = file($rtLog, FILE_IGNORE_NEW_LINES) ?: [];
        $matches = array_filter($lines, fn($l) => str_contains($l, '|' . $ip . '|'));
        if ($matches === []) {
            info('  Жодного запису не знайдено для ' . $ip);
        } else {
            foreach (array_slice($matches, -20) as $line) {
                out(GRAY . '  ' . $line);
            }
            out(GRAY . "  (показано останні " . min(20, count($matches)) . " з " . count($matches) . ")");
        }
    } else {
        info('  og_runtime.log не існує');
    }

    // 5) Історія з blocked-логу (детальніше — з гейтами)
    out(CYAN . "\n  Історія з og_blocked.log:");
    $blkLog = $dataDir . '/og_blocked.log';
    if (is_file($blkLog)) {
        $lines = file($blkLog, FILE_IGNORE_NEW_LINES) ?: [];
        $matches = array_filter($lines, fn($l) => str_contains($l, $ip));
        if ($matches === []) {
            info('  Жодного запису не знайдено для ' . $ip);
        } else {
            foreach (array_slice($matches, -20) as $line) {
                out(GRAY . '  ' . substr($line, 0, 250));
            }
            out(GRAY . "  (показано останні " . min(20, count($matches)) . " з " . count($matches) . ")");
        }
    } else {
        info('  og_blocked.log не існує');
    }

    // 6) Рекомендація
    out(CYAN . "\n  РЕКОМЕНДАЦІЇ:");
    if ($isPerm) {
        warn("  IP у permanent ban-листі. Зняти: php patch.php $offerPath --unban $ip");
    }
    if (is_file($ipf)) {
        $state = $state ?? [];
        if (($state['atk_block'] ?? 0) > time() || ($state['rl_block'] ?? 0) > time()) {
            warn("  IP активно заблоковано. Зняти + whitelist: php patch.php $offerPath --unban $ip");
        }
    }
    if (!$isWl && !$isPerm) {
        info("  Додай у whitelist щоб не банило: php patch.php $offerPath --allow $ip");
    } elseif ($isWl) {
        ok("  IP у вайтлисті — bot-protect не повинен його блокувати.");
        if (is_file($ipf) && (($state['atk_block'] ?? 0) > time() || ($state['rl_block'] ?? 0) > time())) {
            warn("  АЛЕ є активний бан. Зніми state: php patch.php $offerPath --unban $ip");
        }
    }
    out('');
}

/**
 * RECOVERY — аварійна команда коли сайт не вантажиться через залишки OG в HTML/PHP.
 * Не залежить від _og_backup. Сканує весь webroot, знаходить OG-маркери у файлах,
 * вирізає блоки. Якщо файл після зачистки порожній — видаляє файл.
 * Також знімає всі OG-правила з .htaccess і прибирає bot-protect.php (бо без нього
 * залишений PHP-інжект буде фатал-ити).
 */
/**
 * VERIFY — deep audit + auto-fix patcher correctness.
 *
 * Перевіряє:
 *   1. Лінт ВСІХ PHP файлів у webroot (синтаксис після патча)
 *   2. JS блоки в HTML/PHP — баланс дужок/лапок (бо OG inject через str_replace
 *      може давати дисбаланс)
 *   3. bot-protect.php присутність, лінт, права
 *   4. .og_secret існує, читається, розмір ≥32
 *   5. _og_data writable Apache'ом
 *   6. Legacy traces з v3/v4/v5 (рекурсивно)
 *   7. Подвійні OG inject (стрипати має один-в-один)
 *   8. Власник файлів (chown під веб-юзера якщо потрібно)
 *
 * Що ламається — намагається фіксити автоматично.
 * Звітує підсумок: "✓ Все добре" або "N помилок, M зафіксовано, K треба руками".
 */
function og_patch_cli_verify(string $offerPath, string $dataDir, string $backupDir): void
{
    head('VERIFY — deep audit + auto-fix коректності патча');
    $offerPath = rtrim(realpath($offerPath) ?: $offerPath, '/');

    $checks   = 0;
    $passed   = 0;
    $fixed    = 0;
    $manual   = [];

    $check = static function (string $desc, bool $ok, ?callable $autoFix = null) use (&$checks, &$passed, &$fixed, &$manual): void {
        $checks++;
        if ($ok) {
            $passed++;
            ok($desc);
            return;
        }
        if ($autoFix !== null) {
            $fixResult = $autoFix();
            if ($fixResult === true) {
                $fixed++;
                ok($desc . ' (auto-fixed)');
                return;
            }
            if (is_string($fixResult)) {
                $manual[] = $desc . ' → ' . $fixResult;
            } else {
                $manual[] = $desc;
            }
        } else {
            $manual[] = $desc;
        }
        warn($desc);
    };

    // 1. PHP лінт всіх .php у webroot
    info('[1/8] Лінт PHP файлів...');
    $phpFiles = [];
    try {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($offerPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        $it->setMaxDepth(3);
        foreach ($it as $f) {
            if (!$f->isFile()) continue;
            $p = $f->getPathname();
            if (str_contains($p, '/_og_backup/')) continue;
            if (strtolower($f->getExtension()) === 'php') $phpFiles[] = $p;
        }
    } catch (Throwable $e) {}
    $phpBroken = [];
    foreach ($phpFiles as $p) {
        $lintOut = []; $lintRc = 0;
        @exec(escapeshellcmd(PHP_BINARY) . ' -l ' . escapeshellarg($p) . ' 2>&1', $lintOut, $lintRc);
        if ($lintRc !== 0) {
            $phpBroken[] = $p . ' — ' . trim(implode(' ', $lintOut));
        }
    }
    $check(
        'PHP лінт: ' . count($phpFiles) . ' файл(ів)',
        $phpBroken === [],
        $phpBroken === [] ? null : function () use (&$phpBroken) {
            foreach ($phpBroken as $err) {
                warn('  ✗ ' . substr($err, 0, 200));
            }
            return 'manual fix needed для ' . count($phpBroken) . ' PHP файл(ів)';
        }
    );

    // 2. JS sanity в HTML/PHP
    info('[2/8] JS sanity в патчених файлах...');
    $jsBroken = 0;
    $jsTotal = 0;
    foreach ($phpFiles as $p) {
        $src = (string)@file_get_contents($p);
        if (!preg_match_all('/<script\b[^>]*>([\s\S]*?)<\/script\s*>/i', $src, $mm)) continue;
        foreach ($mm[1] as $body) {
            $body = trim($body);
            if ($body === '') continue;
            $jsTotal++;
            if (substr_count($body, '(') !== substr_count($body, ')')
                || substr_count($body, '{') !== substr_count($body, '}')
                || substr_count($body, '[') !== substr_count($body, ']')) {
                $jsBroken++;
            }
        }
    }
    $check(
        "JS sanity: $jsTotal скриптів",
        $jsBroken === 0,
        $jsBroken === 0 ? null : fn() => 'дисбаланс дужок у ' . $jsBroken . ' JS блоків — re-patch або recovery'
    );

    // 3. bot-protect.php
    info('[3/8] bot-protect.php...');
    $bp = $offerPath . '/bot-protect.php';
    if (is_file($bp)) {
        $bpOk = og_patch_is_bot_protect_file($bp);
        $lintOut = []; $lintRc = 0;
        @exec(escapeshellcmd(PHP_BINARY) . ' -l ' . escapeshellarg($bp) . ' 2>&1', $lintOut, $lintRc);
        $bpLintOk = $lintRc === 0;
        $check('bot-protect.php валідний', $bpOk && $bpLintOk,
            ($bpOk && $bpLintOk) ? null : fn() => 'bot-protect.php broken: re-patch потрібен');
    } else {
        $check('bot-protect.php присутній', false, fn() => 'нема файлу — захист не активна, постав через patch.php ... --canonical-host=...');
    }

    // 4. .og_secret
    info('[4/8] .og_secret...');
    $secretFound = '';
    foreach ([dirname($offerPath) . '/.og_secret', $offerPath . '/_og_data/.og_secret'] as $sp) {
        if (is_file($sp)) { $secretFound = $sp; break; }
    }
    if ($secretFound !== '') {
        $sz = filesize($secretFound);
        $st = stat($secretFound);
        $perms = $st['mode'] & 0777;
        $check(
            '.og_secret OK (' . $sz . ' байт, перм 0' . sprintf('%o', $perms) . ')',
            $sz >= 32 && ($perms & 0044) !== 0,
            function () use ($secretFound, $sz) {
                if ($sz < 32) return 'регенерувати: rm ' . $secretFound . ' && re-patch';
                @chmod($secretFound, 0644);
                return true;
            }
        );
    } elseif (is_file($bp)) {
        $check('.og_secret існує', false, fn() => 'нема секрету — bot-protect не зможе шифрувати. Re-patch');
    } else {
        $check('.og_secret (не потрібен якщо нема bot-protect)', true);
    }

    // 5. _og_data writable
    info('[5/8] _og_data writable...');
    if (is_dir($dataDir)) {
        $testFile = $dataDir . '/.og_verify_test';
        $writable = @file_put_contents($testFile, 'x') !== false;
        if ($writable) @unlink($testFile);
        $check('_og_data writable', $writable, function () use ($dataDir) {
            $owner = og_patch_detect_web_owner(dirname($dataDir));
            if ($owner !== null) {
                og_patch_chown_recursive($dataDir, $owner[0], $owner[1]);
                @chmod($dataDir, 0755);
                return true;
            }
            return 'chmod 0755 ' . $dataDir . ' && chown <web-user> ' . $dataDir;
        });
    } elseif (is_file($bp)) {
        $check('_og_data існує', false, function () use ($dataDir) {
            @mkdir($dataDir, 0755, true);
            return is_dir($dataDir) ? true : 'не вдалося створити: ' . $dataDir;
        });
    } else {
        $check('_og_data (не потрібен без bot-protect)', true);
    }

    // 6. Legacy traces (v3/v4/v5)
    info('[6/8] Legacy traces (OG v3/v4/v5)...');
    $legacyFiles = [];
    foreach ($phpFiles as $p) {
        $src = (string)@file_get_contents($p, false, null, 0, 8192);
        if (str_contains($src, '# OfferGuard v3')
            || str_contains($src, '# OfferGuard v4')
            || str_contains($src, '# OfferGuard v5')
            || (str_contains($src, 'data-og-canonical-lock') && !str_contains($src, 'og-challenge'))) {
            $legacyFiles[] = $p;
        }
    }
    // .htaccess теж
    $ht = $offerPath . '/.htaccess';
    if (is_file($ht)) {
        $htSrc = (string)@file_get_contents($ht);
        if (str_contains($htSrc, '# OfferGuard v4') || str_contains($htSrc, '# OfferGuard v3')) {
            $legacyFiles[] = $ht;
        }
    }
    $check(
        'Legacy traces (v3/v4/v5)',
        $legacyFiles === [],
        $legacyFiles === [] ? null : function () use ($legacyFiles) {
            foreach ($legacyFiles as $p) info('  ✗ legacy: ' . $p);
            return 'запусти --recovery (зніме legacy)';
        }
    );

    // 7. Подвійні OG inject (recovery пропустив щось)
    info('[7/8] Подвійні inject / зайві маркери...');
    $doubleInject = [];
    foreach ($phpFiles as $p) {
        $src = (string)@file_get_contents($p);
        if (substr_count($src, '/* [OfferGuard:start] */') > 1) {
            $doubleInject[] = $p . ' (double start)';
        }
        if (substr_count($src, '<!-- [OfferGuard:origin-soft] -->') > 1) {
            $doubleInject[] = $p . ' (double origin-soft)';
        }
    }
    $check(
        'Без подвійних OG-блоків',
        $doubleInject === [],
        $doubleInject === [] ? null : function () use ($doubleInject) {
            foreach ($doubleInject as $d) warn('  ✗ ' . $d);
            return 'rollback + clean re-patch';
        }
    );

    // 8. Власник OG-файлів
    info('[8/8] Власник OG файлів...');
    if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
        $webOwner = og_patch_detect_web_owner($offerPath);
        if ($webOwner !== null && is_file($bp)) {
            $bpSt = stat($bp);
            $ownerOk = $bpSt && $bpSt['uid'] === $webOwner[0];
            $check(
                'Власник bot-protect = uid сайту (' . $webOwner[0] . ')',
                $ownerOk,
                $ownerOk ? null : function () use ($bp, $webOwner, $dataDir, $secretFound) {
                    @chown($bp, $webOwner[0]);
                    @chgrp($bp, $webOwner[1]);
                    if (is_dir($dataDir)) og_patch_chown_recursive($dataDir, $webOwner[0], $webOwner[1]);
                    if ($secretFound !== '') @chown($secretFound, $webOwner[0]);
                    return true;
                }
            );
        } else {
            $check('Власник (не потрібен — нема bot-protect)', true);
        }
    } else {
        info('  (skip: не root)');
        $check('Власник перевірка', true);
    }

    // Summary
    out('');
    $broken = count($manual);
    if ($broken === 0 && $fixed === 0) {
        ok("✓ Все добре. Усі $checks перевірок пройдено.");
    } else {
        if ($fixed > 0) ok("✓ Авто-фіксано: $fixed");
        if ($passed > 0) ok("✓ Пройдено: $passed/$checks");
        if ($broken > 0) {
            warn("✗ Потребує ручної дії: $broken");
            foreach ($manual as $m) info('  → ' . $m);
        }
    }
}

function og_patch_cli_recovery(string $offerPath, bool $dryRun = false): void
{
    head('RECOVERY — аварійна зачистка щоб сайт запрацював');
    $offerPath = rtrim(realpath($offerPath) ?: $offerPath, '/');
    if (!is_dir($offerPath)) {
        fail('Папка не знайдена: ' . $offerPath); return;
    }
    info('Очищаю всі OG-залишки у HTML/PHP файлах без потреби в _og_backup.');
    out('');

    $cleaned = 0;
    $failed = [];

    // 1) Знайти і вирізати OG з усіх HTML/PHP
    try {
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($offerPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iter as $f) {
            if (!$f->isFile()) continue;
            $p = $f->getPathname();
            if (str_contains($p, '/_og_backup/') || str_contains($p, '/.git/')
                || str_contains($p, '/node_modules/') || str_contains($p, '/vendor/')) {
                continue;
            }
            $bn = $f->getFilename();
            if ($bn === 'bot-protect.php') continue;
            $ext = strtolower($f->getExtension());
            if (!in_array($ext, ['php', 'html', 'htm', 'phtml', 'tpl', 'twig', 'blade.php'], true)
                && !str_ends_with(strtolower($bn), '.blade.php')) {
                continue;
            }
            $src = (string)@file_get_contents($p);
            if ($src === '' || !og_patch_file_has_offer_guard_traces($src)) continue;
            $rel = str_replace($offerPath . '/', '', $p);

            // Strip
            if ($ext === 'php' || str_ends_with(strtolower($bn), '.phtml')) {
                $out = og_strip_php_offer_guard_blocks($src);
                // Залишковий wrapper від inject_php_guard
                $out = preg_replace('/<\?php\s*\n@error_reporting\(0\);\s*\n\?>\s*/', '', $out, 1) ?? $out;
                $out = preg_replace('/<\?php\s*\n@error_reporting\(0\);\s*\?>\s*/', '', $out, 1) ?? $out;
            } else {
                $out = og_strip_offer_guard_fragments($src);
            }

            if ($out === $src) continue;

            if ($dryRun) {
                ok("[DRY] Очищу: $rel");
                $cleaned++;
                continue;
            }
            if (trim($out) === '') {
                if (@unlink($p)) {
                    ok("Видалено (тільки OG): $rel");
                    $cleaned++;
                } else {
                    $failed[] = "$rel (unlink fail)";
                }
            } else {
                if (@file_put_contents($p, $out, LOCK_EX) !== false) {
                    ok("Очищено: $rel");
                    $cleaned++;
                } else {
                    $failed[] = "$rel (write fail)";
                }
            }
        }
    } catch (Throwable $e) {
        warn('Помилка обходу: ' . $e->getMessage());
    }

    // 2) Знести bot-protect.php (без нього залишений PHP інжект буде падати)
    foreach (['', '/public_html', '/www', '/htdocs', '/public', '/web'] as $sub) {
        $bp = $offerPath . $sub . '/bot-protect.php';
        if (is_file($bp) && og_patch_is_bot_protect_file($bp)) {
            if ($dryRun) {
                ok('[DRY] Видалю bot-protect.php' . ($sub !== '' ? " in $sub" : ''));
                $cleaned++;
            } elseif (@unlink($bp)) {
                ok('Видалено bot-protect.php' . ($sub !== '' ? " in $sub" : ''));
                $cleaned++;
            }
        }
    }

    // 3) Зняти OG з .htaccess (або видалити якщо тільки OG)
    foreach (['', '/public_html', '/www', '/htdocs', '/public', '/web'] as $sub) {
        $ht = $offerPath . $sub . '/.htaccess';
        if (!is_file($ht)) continue;
        $src = (string)@file_get_contents($ht);
        if (!str_contains($src, '[OfferGuard:') && !str_contains($src, 'OfferGuard v6')
            && !str_contains($src, 'OfferGuard v4') && !str_contains($src, 'OfferGuard v3')
            && !preg_match('/<FilesMatch\s+"[^"]*bot-protect/', $src)) continue;
        $stripped = og_patch_strip_htaccess_offer_guard($src);
        if ($stripped === trim($src)) continue;
        if ($dryRun) {
            ok('[DRY] Очищу .htaccess' . ($sub !== '' ? " in $sub" : ''));
            $cleaned++;
        } elseif (trim($stripped) === '') {
            if (@unlink($ht)) {
                ok('Видалено .htaccess (містив лише OG)' . ($sub !== '' ? " in $sub" : ''));
                $cleaned++;
            }
        } else {
            if (@file_put_contents($ht, $stripped . "\n", LOCK_EX) !== false) {
                ok('OG-блок знято з .htaccess' . ($sub !== '' ? " in $sub" : ''));
                $cleaned++;
            }
        }
    }

    // 4) .og_secret в parent (out-of-tree)
    $parent = dirname($offerPath);
    foreach ([$offerPath . '/.og_secret', $parent . '/.og_secret'] as $sec) {
        if (is_file($sec)) {
            if ($dryRun) { ok('[DRY] Видалю .og_secret: ' . $sec); $cleaned++; }
            elseif (@unlink($sec)) { ok('Видалено .og_secret: ' . $sec); $cleaned++; }
        }
    }

    // 5) АВТО-FIX зламаних include() поза open_basedir у index.php / основних PHP.
    // Багато офферів мають `include('../userdata/head-scripts.php')` де `userdata/`
    // має бути в parent, але HestiaCP open_basedir не пускає вище public_html.
    // Створюємо порожні stub'и для include'ів які індексити НЕ зможе без них.
    // Якщо `userdata/` має бути в parent, але parent НЕ в open_basedir —
    // створюємо у public_html замість parent (це працює, бо include('../X')
    // фактично йде у public_html's parent... але якщо parent заборонений,
    // include тихо зафейлить з warning, але PHP продовжує. Без stub'у warning спамить.
    foreach (['index.php', 'index.html', 'index.htm'] as $idx) {
        $idxPath = $offerPath . '/' . $idx;
        if (!is_file($idxPath)) continue;
        $src = (string)@file_get_contents($idxPath, false, null, 0, 8192);
        if (!preg_match_all('/(?:include|require)(?:_once)?\s*\(?\s*[\'"]([^\'"]+\.php)[\'"]/i', $src, $mm)) {
            continue;
        }
        foreach (array_unique($mm[1]) as $incPath) {
            if (!str_starts_with($incPath, '../') && !str_starts_with($incPath, './')) continue;
            // Резолвимо відносно офферу.
            $abs = realpath($offerPath . '/' . $incPath);
            if ($abs !== false && is_file($abs)) continue; // файл є — OK
            // Створюємо порожній stub у тому ж відносному місці, якщо це безпечно.
            $target = $offerPath . '/' . $incPath;
            // Захист: не виходимо вище parent домену
            $domainRoot = dirname($offerPath);
            $resolvedTarget = realpath(dirname($target));
            if ($resolvedTarget !== false && !str_starts_with($resolvedTarget, $domainRoot)) {
                continue;
            }
            $targetDir = dirname($target);
            if (!is_dir($targetDir)) {
                if ($dryRun) {
                    info('[DRY] Створю каталог: ' . $targetDir);
                } else {
                    @mkdir($targetDir, 0755, true);
                }
            }
            if (!$dryRun && is_dir($targetDir) && @file_put_contents($target, "<?php // OfferGuard auto-stub for missing offer include\n") !== false) {
                ok('Створено stub: ' . str_replace($domainRoot . '/', '', $target));
                $cleaned++;
                // chown під веб-юзера якщо ми root
                if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
                    $owner = og_patch_detect_web_owner($offerPath);
                    if ($owner !== null) {
                        @chown($target, $owner[0]);
                        @chgrp($target, $owner[1]);
                        @chown($targetDir, $owner[0]);
                        @chgrp($targetDir, $owner[1]);
                    }
                }
            }
        }
    }

    out('');
    if ($failed === []) {
        ok("✓ Recovery завершено. Виправлено $cleaned об'єктів.");
        info('Відкрий сайт у браузері — повинен вантажитись (без OG-захисту).');
        info('Якщо хочеш знов поставити захист: php patch.php ' . $offerPath . ' --canonical-host=…');
    } else {
        warn("Recovery завершено з помилками ($cleaned ОК, " . count($failed) . ' fail):');
        foreach ($failed as $f) warn('  ' . $f);
    }
}

/** @return array{ts:string,lvl:string,src:string,rid:string,ip:string,uri:string,fn:string,msg:string,ctx:string,raw:string}|null */
function og_patch_parse_xlog_line(string $line): ?array
{
    $line = trim($line);
    if ($line === '') {
        return null;
    }
    $parts = explode('|', $line);
    if (count($parts) < 7) {
        return null;
    }
    $entry = [
        'ts' => $parts[0],
        'lvl' => strtoupper($parts[1]),
        'src' => strtolower($parts[2]),
        'rid' => $parts[3],
        'ip' => $parts[4],
        'uri' => $parts[5],
        'fn' => '',
        'msg' => '',
        'ctx' => '',
        'raw' => $line,
    ];
    $tail = implode('|', array_slice($parts, 6));
    if (preg_match('/fn=([^|]*)/', $tail, $m)) {
        $entry['fn'] = $m[1];
    }
    if (preg_match('/msg=([^|]*)/', $tail, $m)) {
        $entry['msg'] = $m[1];
    }
    if (preg_match('/\|ctx=(.*)$/s', $tail, $m)) {
        $entry['ctx'] = $m[1];
    }
    return $entry;
}

function og_patch_xlog_level_rank(string $lvl): int
{
    static $r = ['DEBUG' => 10, 'INFO' => 20, 'WARN' => 30, 'ERROR' => 40, 'FATAL' => 50];
    return $r[strtoupper($lvl)] ?? 0;
}

/**
 * Live tail на логи bot-protect (як `tail -f`).
 * Працює навіть без `tail`-команди — читає файли в циклі.
 */
function og_patch_cli_tty_raw(bool $on): void
{
    if (!og_patch_cli_is_tty()) {
        return;
    }
    static $saved = null;
    if ($on) {
        if ($saved === null) {
            $saved = @shell_exec('stty -g 2>/dev/null');
        }
        @shell_exec('stty -echo -icanon min 0 time 1 2>/dev/null');
    } elseif ($saved !== null && is_string($saved) && $saved !== '') {
        @shell_exec('stty ' . $saved . ' 2>/dev/null');
    } else {
        @shell_exec('stty echo icanon 2>/dev/null');
    }
}

/** @return string|null left|right|up|down|quit|clear|tab1|tab2|tab3|tab4 */
function og_patch_cli_read_nav_key(int $waitUs = 180000): ?string
{
    $read = [STDIN];
    $w = $e = null;
    if (@stream_select($read, $w, $e, 0, $waitUs) <= 0) {
        return null;
    }
    $ch = fread(STDIN, 1);
    if ($ch === false || $ch === '') {
        return null;
    }
    if ($ch === "\033") {
        $ch2 = fread(STDIN, 1);
        $ch3 = fread(STDIN, 1);
        if ($ch2 === '[' && $ch3 !== false) {
            return match ($ch3) {
                'D' => 'left',
                'C' => 'right',
                'A' => 'up',
                'B' => 'down',
                default => null,
            };
        }
        return null;
    }
    if ($ch === 'q' || $ch === 'Q' || $ch === "\x03") {
        return 'quit';
    }
    if ($ch === 'c' || $ch === 'C') {
        return 'clear';
    }
    if ($ch === "\n" || $ch === "\r") {
        return 'enter';
    }
    if ($ch >= '1' && $ch <= '4') {
        return 'tab' . $ch;
    }
    if (ctype_digit($ch)) {
        return 'digit' . $ch;
    }
    return null;
}

function og_patch_cli_term_size(): array
{
    $rows = 24;
    $cols = 100;
    if (!og_patch_cli_is_tty()) {
        return [$rows, $cols];
    }
    $stty = @shell_exec('stty size 2>/dev/null');
    if (is_string($stty) && preg_match('/^\s*(\d+)\s+(\d+)/m', trim($stty), $m)) {
        return [max(12, (int)$m[1]), max(40, (int)$m[2])];
    }
    $r = @shell_exec('tput lines 2>/dev/null');
    $c = @shell_exec('tput cols 2>/dev/null');
    if (is_string($r) && ctype_digit(trim($r))) {
        $rows = max(12, (int)trim($r));
    }
    if (is_string($c) && ctype_digit(trim($c))) {
        $cols = max(40, (int)trim($c));
    }
    return [$rows, $cols];
}

function og_patch_mon_shorten_body(string $body): string
{
    $body = preg_replace('#/home/[^/]+/(?:web/)?[^/]+/public_html#', '…/public_html', $body) ?? $body;
    if (preg_match('/file_put_contents\([^)]+\)[^:]*:\s*(.+)$/i', $body, $m)) {
        return 'fp_err: ' . trim($m[1]);
    }
    if (preg_match('/Permission denied/i', $body) && strlen($body) > 80) {
        return 'Permission denied (_og_data — chmod/chown, див. --doctor)';
    }
    return $body;
}

function og_patch_mon_format_plain(string $tag, string $line, int $cols): string
{
    static $colors = [
        'access' => B, 'runtime' => CYAN, 'blocked' => RED,
        'traffic' => GRAY, 'sessions' => YEL, 'xlog' => MAG,
    ];
    $raw = rtrim($line);
    $isErr = (bool)preg_match('/\|ERR\b|ERR\[\d+\]|Permission denied|Fatal error/i', $raw);
    $col = $isErr ? RED . B : ($colors[$tag] ?? GRAY);
    $body = og_patch_mon_shorten_body($raw);
    $prefix = '[' . str_pad($tag, 7) . '] ';
    $maxBody = max(12, $cols - 2 - og_ansi_len($prefix) - 2);
    if (og_ansi_len($body) > $maxBody) {
        $body = substr($body, 0, max(8, $maxBody - 1)) . '…';
    }
    return $col . $prefix . $body . R;
}

/**
 * @param array{tag:string,raw:string,kind:string,dup?:int} $entry
 */
function og_patch_mon_render_entry(array $entry, int $cols): string
{
    $dup = (int)($entry['dup'] ?? 1);
    $sfx = $dup > 1 ? GRAY . ' ×' . $dup . R : '';
    $kind = $entry['kind'];
    $raw = $entry['raw'];
    $tag = $entry['tag'];
    if ($kind === 'xlog') {
        $fx = og_patch_mon_format_xlog($raw, $cols);
        return ($fx ?? '') . $sfx;
    }
    if ($kind === 'traffic') {
        $ft = og_patch_mon_format_traffic($raw, $cols);
        return ($ft ?? '') . $sfx;
    }
    return og_patch_mon_format_plain($tag, $raw, $cols) . $sfx;
}

/** @param array{tag:string,raw:string,kind:string,dup?:int} $entry */
function og_patch_mon_entry_visual_rows(array $entry, int $cols): int
{
    return substr_count(og_patch_mon_render_entry($entry, $cols), "\n") + 1;
}

/**
 * @param list<array{tag:string,raw:string,kind:string,dup?:int}> $entries
 * @return list<array{tag:string,raw:string,kind:string,dup?:int}>
 */
function og_patch_mon_slice_for_rows(array $entries, int $maxRows, int $cols): array
{
    $out = [];
    $used = 0;
    for ($i = count($entries) - 1; $i >= 0; $i--) {
        $need = og_patch_mon_entry_visual_rows($entries[$i], $cols);
        if ($used + $need > $maxRows && $out !== []) {
            break;
        }
        array_unshift($out, $entries[$i]);
        $used += $need;
        if ($used >= $maxRows) {
            break;
        }
    }
    return $out;
}

function og_patch_mon_format_xlog(string $line, int $cols): ?string
{
    $e = og_patch_parse_xlog_line($line);
    if ($e === null) {
        return null;
    }
    static $lvlColor = null;
    if ($lvlColor === null) {
        $lvlColor = static function (string $lvl): string {
            return match (strtoupper($lvl)) {
                'DEBUG' => GRAY, 'INFO' => CYAN, 'WARN' => YEL,
                'ERROR', 'FATAL' => RED, default => '',
            };
        };
    }
    $col = $lvlColor($e['lvl']);
    $ts = strlen($e['ts']) >= 19 ? substr($e['ts'], 11, 12) : $e['ts'];
    $uri = strlen($e['uri']) > 18 ? substr($e['uri'], 0, 17) . '…' : $e['uri'];
    $fn = strlen($e['fn']) > 16 ? substr($e['fn'], 0, 15) . '…' : $e['fn'];
    $msg = $e['msg'] !== '' ? $e['msg'] : '—';
    $row = $col . str_pad($ts, 12) . ' ' . str_pad($e['lvl'], 5) . ' '
        . ($e['src'] === 'cli' ? CYAN . 'cli' . R . $col : 'srv') . ' '
        . str_pad($fn, 17) . ' ' . substr($msg, 0, max(10, $cols - 52));
    if ($e['ctx'] !== '' && in_array($e['lvl'], ['WARN', 'ERROR', 'FATAL'], true)) {
        $ctx = strlen($e['ctx']) > $cols - 6 ? substr($e['ctx'], 0, $cols - 9) . '…' : $e['ctx'];
        $row .= GRAY . "\n       └ " . YEL . $ctx . R;
    }
    return $row . R;
}

function og_patch_mon_format_traffic(string $line, int $cols): ?string
{
    $p = explode("\t", trim($line));
    // Traffic log format: 18 fields (see write site near 'if ($C[traffic_log_on])').
    // Minimum we need for a meaningful row is 7. Lines shorter than that are
    // either a truncated write or a format mismatch from an older bot-protect.php —
    // return null so the monitor falls back to plain-print instead of mis-rendering.
    if (count($p) < 7) {
        return null;
    }
    [$ts, $lip, $lclass, $susp, $lm, $luri, $ltype] = $p;
    $col = match ($lclass) {
        'bot', 'crawler' => RED,
        'suspect', 'proxy' => YEL,
        default => GREEN,
    };
    // Show time, IP, class, suspect-score, method, type-tag, then URI fills the rest.
    // Time:19 IP:15 class:8 susp:3 method:4 type:6 = 55 cols of fixed prefix + spaces.
    $fixedWidth = 19 + 1 + 15 + 1 + 8 + 1 + 3 + 1 + 4 + 1 + 6 + 1;
    $uriBudget  = max(20, $cols - $fixedWidth - 4);
    $suspS      = str_pad((string)((int)$susp), 3, ' ', STR_PAD_LEFT);
    $typeS      = $ltype === 'static' ? 'static' : 'html';
    return $col . str_pad($ts, 19) . ' ' . str_pad($lip, 15) . ' '
        . str_pad($lclass, 8) . ' ' . $suspS . ' '
        . str_pad($lm, 4) . ' ' . str_pad($typeS, 6) . ' '
        . substr($luri, 0, $uriBudget) . R;
}

/**
 * Live-мониторинг з вкладками (←→). Усі файли читаються паралельно.
 */
function og_patch_cli_monitoring_live(string $offerPath, string $dataDir, int $startTab = 0): void
{
    if (!is_dir($dataDir)) {
        warn("_og_data не існує: $dataDir");
        return;
    }

    $tabLabels = ['ВСЕ', 'XLog', 'Трафік', 'Runtime'];
    $tabCount = count($tabLabels);
    $activeTab = max(0, min($tabCount - 1, $startTab));

    $streamDefs = [
        'access'   => ['file' => 'og_access.log',   'tabs' => [0, 3], 'fmt' => 'plain'],
        'runtime'  => ['file' => 'og_runtime.log',  'tabs' => [0, 3], 'fmt' => 'plain'],
        'blocked'  => ['file' => 'og_blocked.log',  'tabs' => [0, 3], 'fmt' => 'plain'],
        'sessions' => ['file' => 'og_sessions.log', 'tabs' => [0, 3], 'fmt' => 'plain'],
        'traffic'  => ['file' => 'og_traffic.log',  'tabs' => [0, 2], 'fmt' => 'traffic'],
        'xlog'     => ['file' => 'og_xlog.log',     'tabs' => [0, 1], 'fmt' => 'xlog'],
    ];

    $positions = [];
    $paths = [];
    foreach ($streamDefs as $tag => $def) {
        $paths[$tag] = $dataDir . '/' . $def['file'];
        $positions[$tag] = is_file($paths[$tag]) ? (int)filesize($paths[$tag]) : 0;
    }

    [$initRows] = og_patch_cli_term_size();
    $bufMax = max(100, ($initRows - 6) * 4);
    /** @var list<array{tag:string,raw:string,kind:string,dup?:int}>[] */
    $bufs = array_fill(0, $tabCount, []);
    $errFlash = 0;

    $pushEntry = static function (int $tab, string $tag, string $raw, string $kind) use (&$bufs, &$bufMax, &$errFlash): void {
        $n = count($bufs[$tab]);
        if ($n > 0) {
            $last = $bufs[$tab][$n - 1];
            if ($last['tag'] === $tag && $last['raw'] === $raw && $last['kind'] === $kind) {
                $bufs[$tab][$n - 1]['dup'] = ((int)($last['dup'] ?? 1)) + 1;
                if ($tab === 1 && preg_match('/\|ERROR\||\|FATAL\|/i', $raw)) {
                    $errFlash++;
                }
                return;
            }
        }
        $bufs[$tab][] = ['tag' => $tag, 'raw' => $raw, 'kind' => $kind, 'dup' => 1];
        if (count($bufs[$tab]) > $bufMax) {
            array_shift($bufs[$tab]);
        }
        if ($tab === 1 && preg_match('/\|ERROR\||\|FATAL\|/i', $raw)) {
            $errFlash++;
        }
    };

    $ingestLine = static function (string $tag, string $line, array $def) use ($pushEntry): void {
        $kind = $def['fmt'];
        $pushEntry(0, $tag, $line, 'plain');
        foreach ($def['tabs'] as $ti) {
            if ($ti === 0) {
                continue;
            }
            if ($ti === 1 && $kind === 'xlog') {
                $pushEntry(1, $tag, $line, 'xlog');
            } elseif ($ti === 2 && $kind === 'traffic') {
                $pushEntry(2, $tag, $line, 'traffic');
            } elseif ($ti === 3 && $kind === 'plain') {
                $pushEntry(3, $tag, $line, 'plain');
            }
        }
    };

    // Початковий хвіст (останні ~25 KB кожного файлу)
    foreach ($streamDefs as $tag => $def) {
        $f = $paths[$tag];
        if (!is_file($f) || filesize($f) === 0) {
            continue;
        }
        $sz = (int)filesize($f);
        $start = max(0, $sz - 25000);
        $fh = @fopen($f, 'rb');
        if (!$fh) {
            continue;
        }
        if ($start > 0) {
            fseek($fh, $start);
        }
        while (($ln = fgets($fh)) !== false) {
            $ln = trim($ln);
            if ($ln === '') {
                continue;
            }
            $ingestLine($tag, $ln, $def);
        }
        fclose($fh);
        $positions[$tag] = $sz;
    }

    if (!og_patch_cli_is_tty()) {
        head('WATCH — live (без TTY, тільки вкладка ВСЕ)');
        [, $cols] = og_patch_cli_term_size();
        foreach ($bufs[0] as $ent) {
            foreach (explode("\n", og_patch_mon_render_entry($ent, $cols)) as $sub) {
                out('  ' . $sub);
            }
        }
        og_patch_cli_watch_logs_legacy_poll($offerPath, $dataDir, $paths, $positions, $streamDefs, $ingestLine);
        return;
    }

    $running = true;
    $resizePending = false;
    $lastRows = 0;
    $lastCols = 0;
    if (function_exists('pcntl_async_signals')) {
        pcntl_async_signals(true);
    }
    if (function_exists('pcntl_signal')) {
        pcntl_signal(SIGINT, static function () use (&$running): void { $running = false; });
        pcntl_signal(SIGTERM, static function () use (&$running): void { $running = false; });
        if (defined('SIGWINCH')) {
            pcntl_signal(SIGWINCH, static function () use (&$resizePending): void { $resizePending = true; });
        }
    }

    $draw = static function () use (
        &$bufs, &$activeTab, $tabLabels, $tabCount, &$errFlash, $dataDir,
        &$lastRows, &$lastCols, &$bufMax
    ): void {
        [$rows, $cols] = og_patch_cli_term_size();
        $lastRows = $rows;
        $lastCols = $cols;
        $bufMax = max(100, ($rows - 6) * 4);
        $logoRows = count(og_patch_cli_logo_lines(min($cols, 100), $rows)) + 1;
        $headerRows = $logoRows + 2;
        $footerRows = 3;
        $bodyRows = max(4, $rows - $headerRows - $footerRows);
        $sep = str_repeat('─', max(20, $cols - 2));

        echo "\033[H\033[2J\033[J";
        echo "\033[?25l";
        out('');
        $liveLogoCols = $rows < 28 ? min($cols, 50) : min($cols, 100);
        foreach (og_patch_cli_logo_lines($liveLogoCols, $rows) as $logoLn) {
            out($logoLn);
        }
        out(og_patch_cli_center('LIVE MONITOR  |  _og_data  |  ' . $rows . 'x' . $cols, $cols, YEL . B, R));
        $liveTabs = $tabLabels;
        if ($errFlash > 0) {
            $liveTabs[1] = $tabLabels[1] . '*' . min(9, $errFlash);
        }
        out(og_patch_cli_tab_bar($liveTabs, $activeTab));
        out(GRAY . '  ' . $sep . R);

        $slice = og_patch_mon_slice_for_rows($bufs[$activeTab], $bodyRows, $cols);
        if ($slice === []) {
            out(GRAY . '  (порожньо — відкрий сайт або дочекайся запитів)' . R);
        } else {
            foreach ($slice as $ent) {
                foreach (explode("\n", og_patch_mon_render_entry($ent, $cols)) as $sub) {
                    if ($sub !== '') {
                        out('  ' . $sub);
                    }
                }
            }
        }
        out(GRAY . '  ' . $sep . R);
        $help = 'Left/Right: вкладка   c: очистити   q: меню';
        if (og_ansi_len($help) > $cols - 4) {
            $help = 'L/R вкладка · c · q';
        }
        out(GRAY . '  ' . $help . R);
        $pathTail = strlen($dataDir) > $cols - 4 ? '…' . substr($dataDir, -($cols - 5)) : $dataDir;
        out(GRAY . '  ' . $pathTail . R);
    };

    og_patch_cli_tty_raw(true);
    $draw();
    $dirty = false;

    while ($running) {
        [$curRows, $curCols] = og_patch_cli_term_size();
        if ($resizePending || $curRows !== $lastRows || $curCols !== $lastCols) {
            $resizePending = false;
            $dirty = true;
        }

        $gotData = false;
        foreach ($streamDefs as $tag => $def) {
            $f = $paths[$tag];
            if (!is_file($f)) {
                continue;
            }
            clearstatcache(true, $f);
            $size = (int)filesize($f);
            $pos = $positions[$tag] ?? 0;
            if ($size < $pos) {
                $pos = 0;
            }
            if ($size <= $pos) {
                continue;
            }
            $fh = @fopen($f, 'rb');
            if (!$fh) {
                continue;
            }
            fseek($fh, $pos);
            while (($ln = fgets($fh)) !== false) {
                $ln = trim($ln);
                if ($ln === '') {
                    continue;
                }
                $ingestLine($tag, $ln, $def);
                $gotData = true;
            }
            fclose($fh);
            $positions[$tag] = $size;
        }

        $key = og_patch_cli_read_nav_key(120000);
        if ($key === 'quit') {
            $running = false;
            break;
        }
        if ($key === 'clear') {
            for ($i = 0; $i < $tabCount; $i++) {
                $bufs[$i] = [];
            }
            $errFlash = 0;
            $dirty = true;
        } elseif ($key === 'left') {
            $activeTab = ($activeTab - 1 + $tabCount) % $tabCount;
            $dirty = true;
        } elseif ($key === 'right') {
            $activeTab = ($activeTab + 1) % $tabCount;
            $dirty = true;
        } elseif ($key !== null && str_starts_with($key, 'tab')) {
            $activeTab = max(0, min($tabCount - 1, (int)substr($key, 3) - 1));
            $dirty = true;
        }

        if ($gotData) {
            $dirty = true;
        }

        if ($dirty) {
            $draw();
            $dirty = false;
        }

        if (function_exists('pcntl_signal_dispatch')) {
            pcntl_signal_dispatch();
        }
    }

    echo "\033[?25h";
    og_patch_cli_tty_raw(false);
    og_patch_cli_clear_screen();
    out(GRAY . "\n  live-мониторинг завершено.\n" . R);
}

/** Fallback poll без TTY (тільки друк у stdout). */
function og_patch_cli_watch_logs_legacy_poll(
    string $offerPath,
    string $dataDir,
    array $paths,
    array $positions,
    array $streamDefs,
    callable $ingestLine
): void {
    $running = true;
    if (function_exists('pcntl_signal')) {
        pcntl_async_signals(true);
        pcntl_signal(SIGINT, static function () use (&$running): void { $running = false; });
    }
    while ($running) {
        foreach ($streamDefs as $tag => $def) {
            $f = $paths[$tag];
            if (!is_file($f)) {
                continue;
            }
            clearstatcache(true, $f);
            $size = (int)filesize($f);
            $pos = $positions[$tag] ?? 0;
            if ($size < $pos) {
                $pos = 0;
            }
            if ($size <= $pos) {
                continue;
            }
            $fh = @fopen($f, 'rb');
            if (!$fh) {
                continue;
            }
            fseek($fh, $pos);
            while (($ln = fgets($fh)) !== false) {
                $ln = trim($ln);
                if ($ln !== '') {
                    $ingestLine($tag, $ln, $def);
                    out('  ' . og_patch_mon_format_plain($tag, $ln, 100));
                }
            }
            fclose($fh);
            $positions[$tag] = $size;
        }
        usleep(400000);
        if (function_exists('pcntl_signal_dispatch')) {
            pcntl_signal_dispatch();
        }
    }
}

function og_patch_cli_watch_logs(string $offerPath, string $dataDir): void
{
    og_patch_cli_monitoring_live($offerPath, $dataDir, 0);
}


function og_patch_cli_show_whitelist(string $offerPath, string $dataDir): void
{
    head('ВАЙТЛИСТ');
    $wlFile = $dataDir . '/whitelist.txt';
    if (!is_file($wlFile) || !filesize($wlFile)) {
        ok('Вайтлист пуст');
        out(GRAY . "  Добавить: php patch.php $offerPath --allow <IP>");
    } else {
        $list = file($wlFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        out(CYAN . '  Записей: ' . count($list));
        out(GRAY . '  ' . str_repeat('─', 30));
        foreach ($list as $line) {
            out(GREEN . '  ✓  ' . trim($line));
        }
        out('');
        out(GRAY . "  Удалить: php patch.php $offerPath --deny <IP>");
    }
    out('');
}


function og_patch_cli_unban_ip(string $offerPath, string $dataDir, string $unbanIp): bool
{
    head("РАЗБАН: $unbanIp");
    if (!filter_var($unbanIp, FILTER_VALIDATE_IP)) {
        fail("Некорректный IP-адрес: $unbanIp");
        out('');
        return false;
    }
    $iph = md5($unbanIp);
    $ipf = $dataDir . '/' . $iph . '.json';
    $hadState = is_file($ipf);
    $changed = og_clear_ip_ban_state_all($offerPath, $dataDir, $unbanIp);
    ok("Очищены active ban/suspect/live/preflight флаги: $unbanIp");
    if ($changed['perm']) ok("Удалён из перманентного бан-листа: $unbanIp");
    if (!$hadState && !$changed['perm']) warn("Старой записи не было, создана чистая запись: $unbanIp");
    // ВАЖНО: автоматическое добавление в whitelist ОТКЛЮЧЕНО.
    // Причина: WL-ветка bot-protect.php — упрощённый дублёр live-гейта, в нём нет SSE,
    // нет ротации kfrag и livePub-токена; для реального пользователя оффер ломается
    // тихо (пустой #og-content после decrypt). WL — только для серверных тестов.
    // Чтобы добавить IP в whitelist явно: php patch.php <path> --allow <IP>.
    if (!is_dir($dataDir)) @mkdir($dataDir, 0755, true);
    $wlFile = $dataDir . '/whitelist.txt';
    $list = is_file($wlFile) ? (file($wlFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []) : [];
    $list = array_map('trim', $list);
    if (in_array($unbanIp, $list, true)) {
        warn("IP $unbanIp у whitelist — це може ламати оффер у браузері (WL-ветка не має повного crypto-flow).");
        out(GRAY . "  Прибрати: php patch.php $offerPath --deny $unbanIp" . R);
    }
    out('');
    return true;
}


function og_patch_cli_read_ip_states(string $dataDir): array
{
    $states = [];
    if (!is_dir($dataDir)) { return $states; }
    foreach (glob($dataDir . '/*.json') ?: [] as $f) {
        if (!preg_match('/^[a-f0-9]{32}\.json$/', basename($f))) { continue; }
        $d = json_decode((string)@file_get_contents($f), true) ?? [];
        if (empty($d['ip'])) { continue; }
        $states[] = $d;
    }
    return $states;
}

function og_patch_cli_ban_ip(string $offerPath, string $dataDir, string $banIp, int $durationSec = 86400 * 30): bool
{
    if (!filter_var($banIp, FILTER_VALIDATE_IP)) {
        fail("Некорректный IP-адрес: $banIp");
        return false;
    }
    if (!is_dir($dataDir)) { @mkdir($dataDir, 0755, true); }
    $iph  = md5($banIp);
    $ipf  = $dataDir . '/' . $iph . '.json';
    $data = is_file($ipf) ? (json_decode((string)@file_get_contents($ipf), true) ?? []) : [];
    $data['ip']         = $banIp;
    $data['atk_block']  = time() + $durationSec;
    $data['ban_reason'] = 'manual_cli';
    $data['ban_source'] = 'cli';
    $data['ban_ts']     = time();
    @file_put_contents($ipf, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    $days = $durationSec >= 86400 * 365 * 10 ? '∞' : round($durationSec / 86400) . 'д';
    ok("  Забанен на $days: $banIp");
    return true;
}

function og_patch_cli_bans_interactive(string $offerPath, string $dataDir, callable $ogAsk): void
{
    OG_BANS_SCREEN:
    og_patch_cli_clear_screen();
    $now    = time();
    $states = og_patch_cli_read_ip_states($dataDir);

    // Load perm ban list
    $permBans = [];
    $permFile = $dataDir . '/perm_ban.txt';
    if (is_file($permFile)) {
        foreach (file($permFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $pip) {
            $pip = trim($pip);
            if ($pip) { $permBans[$pip] = true; }
        }
    }

    // Load whitelist
    $whitelist = [];
    $wlFile = $dataDir . '/whitelist.txt';
    if (is_file($wlFile)) {
        foreach (file($wlFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $wip) {
            $wip = trim($wip);
            if ($wip) { $whitelist[$wip] = true; }
        }
    }

    // Detect own SSH IP
    $selfIp = '';
    $sshConn = (string)(getenv('SSH_CONNECTION') ?: '');
    if ($sshConn !== '') {
        $pts = preg_split('/\s+/', trim($sshConn));
        if (isset($pts[0]) && filter_var($pts[0], FILTER_VALIDATE_IP)) { $selfIp = $pts[0]; }
    }

    // Classify states: online (last 5 min), banned
    $onlineRows = [];
    $bannedRows = [];
    foreach ($states as $d) {
        $ip       = (string)$d['ip'];
        $atk      = (!empty($d['atk_block']) && $d['atk_block'] > $now) ? (int)$d['atk_block'] : 0;
        $rl       = (!empty($d['rl_block'])  && $d['rl_block']  > $now) ? (int)$d['rl_block']  : 0;
        $isBanned = $atk > 0 || $rl > 0 || isset($permBans[$ip]);

        // Last request from ts array
        $ts    = is_array($d['ts'] ?? null) ? $d['ts'] : [];
        $lastTs = $ts ? max($ts) : (int)($d['last_ts'] ?? 0);
        $ageSec = $lastTs > 0 ? ($now - $lastTs) : PHP_INT_MAX;

        $isOnline = $ageSec <= 300 && !$isBanned;

        $row = [
            'ip'       => $ip,
            'atk'      => $atk,
            'rl'       => $rl,
            'perm'     => isset($permBans[$ip]),
            'wl'       => isset($whitelist[$ip]),
            'self'     => $ip === $selfIp,
            'lastTs'   => $lastTs,
            'ageSec'   => $ageSec,
            'human'    => (int)($d['human_score'] ?? 0),
            'suspect'  => (int)($d['suspect'] ?? 0),
            'reason'   => (string)($d['ban_reason'] ?? ''),
            'strikes'  => (int)($d['strikes_total'] ?? 0),
            'req'      => count($ts),
        ];

        if ($isOnline) {
            $onlineRows[] = $row;
        }
        if ($isBanned) {
            $bannedRows[] = $row;
        }
    }
    // Sort online by most recent first, banned by type
    usort($onlineRows, fn($a, $b) => $a['ageSec'] <=> $b['ageSec']);
    usort($bannedRows, fn($a, $b) => ($b['atk'] ? 2 : ($b['perm'] ? 1 : 0)) <=> ($a['atk'] ? 2 : ($a['perm'] ? 1 : 0))
        ?: strcmp($a['ip'], $b['ip']));

    // Build indexed list for selection
    $idx = [];
    $n   = 1;

    // ── Header ────────────────────────────────────────────────────
    $boxW = 64;
    out('');
    out(CYAN . '  ╔' . str_repeat('═', $boxW - 2) . '╗');
    $h1 = ' БАНЫ & ОНЛАЙН ';
    $h2 = ' ' . basename($offerPath) . ' ';
    $pad = $boxW - 2 - mb_strlen($h1) - mb_strlen($h2);
    out(CYAN . '  ║' . B . $h1 . R . CYAN . str_repeat(' ', max(0, $pad)) . GRAY . $h2 . CYAN . '║');
    out(CYAN . '  ╚' . str_repeat('═', $boxW - 2) . '╝');

    // ── Online users ───────────────────────────────────────────────
    out('');
    out(B . GREEN . '  ● ОНЛАЙН' . R . GRAY . '  (активні за останні 5 хв)' . R);
    out(GRAY . '  ' . str_repeat('─', $boxW - 2));
    if (empty($onlineRows)) {
        out(GRAY . '    немає активних відвідувачів');
    } else {
        out(GRAY . B . '  ' . str_pad('#', 4)
            . str_pad('IP', 18) . str_pad('Активність', 12)
            . str_pad('Людськ.', 9) . str_pad('Підозра', 9) . 'WL');
        out(GRAY . '  ' . str_repeat('─', $boxW - 2));
        foreach ($onlineRows as $row) {
            $ageStr = $row['ageSec'] < 10  ? 'щойно' :
                     ($row['ageSec'] < 60  ? $row['ageSec'] . 'с тому' :
                      floor($row['ageSec'] / 60) . 'хв тому');
            $humanStr  = $row['human'] . '/12';
            $suspStr   = $row['suspect'] > 0 ? RED . '⚠ ' . $row['suspect'] . R : GRAY . '—';
            $wlStr     = $row['wl'] ? GREEN . '✓' : GRAY . '—';
            $selfMark  = $row['self'] ? YEL . ' ◀ ВИ' . R : '';
            out(GREEN . '  ' . B . str_pad((string)$n, 4) . R
                . str_pad($row['ip'] . $selfMark, 18)
                . GRAY . str_pad($ageStr, 12)
                . (($row['human'] >= 8) ? GREEN : ($row['human'] >= 4 ? YEL : RED))
                . str_pad($humanStr, 9) . R
                . $suspStr . R . str_pad('', 4)
                . $wlStr . R);
            $idx[$n] = ['type' => 'online', 'row' => $row];
            $n++;
        }
    }

    // ── Banned users ───────────────────────────────────────────────
    out('');
    out(B . RED . '  ● АКТИВНІ БАНИ' . R);
    out(GRAY . '  ' . str_repeat('─', $boxW - 2));
    if (empty($bannedRows)) {
        out(GRAY . '    немає активних банів');
    } else {
        out(GRAY . B . '  ' . str_pad('#', 4)
            . str_pad('IP', 18) . str_pad('Тип бану', 14)
            . str_pad('Залишилось', 12) . 'Причина');
        out(GRAY . '  ' . str_repeat('─', $boxW - 2));
        foreach ($bannedRows as $row) {
            $ip   = $row['ip'];
            $type = $row['perm']    ? 'permanent'  :
                   ($row['atk'] > 0 ? 'attack'     : 'rate-limit');
            $until = $row['atk'] ?: $row['rl'];
            $remStr = $until > 0 ? og_remaining($until) : '∞';
            $col   = $type === 'permanent' || $type === 'attack' ? RED : YEL;
            $reason = $row['reason'] !== '' ? $row['reason'] : ($row['perm'] ? 'perm_ban.txt' : '—');
            $selfMark = $row['self'] ? YEL . ' ◀ ВИ' . R : '';
            out($col . '  ' . B . str_pad((string)$n, 4) . R
                . $col . str_pad($ip . $selfMark, 24) . R
                . $col . str_pad($type, 14)
                . CYAN . str_pad($remStr, 12)
                . GRAY . substr($reason, 0, 22));
            $idx[$n] = ['type' => 'banned', 'row' => $row];
            $n++;
        }
    }

    // ── Footer / prompt ────────────────────────────────────────────
    out('');
    out(GRAY . '  ' . str_repeat('─', $boxW - 2));
    out('  ' . GRAY . 'Введіть ' . B . '#номер' . R . GRAY . ' для дії, '
        . B . 'IP' . R . GRAY . ' для пошуку/бану, або ' . B . '0' . R . GRAY . ' — назад');
    out('');
    $input = trim((string)($ogAsk)('  >', '0'));

    if ($input === '0' || $input === '') { return; }

    // Numeric selection from list
    if (ctype_digit($input) && isset($idx[(int)$input])) {
        $sel = $idx[(int)$input];
        $ip  = $sel['row']['ip'];
        $row = $sel['row'];
        og_patch_cli_clear_screen();
        out('');
        $isBanned = $row['atk'] > 0 || $row['rl'] > 0 || $row['perm'];
        $isWl     = $row['wl'];
        $ageStr   = $row['ageSec'] < PHP_INT_MAX
            ? ($row['ageSec'] < 60 ? $row['ageSec'] . 'с тому' : floor($row['ageSec'] / 60) . 'хв тому')
            : '—';
        $statusStr = $isBanned
            ? RED . '⛔ ЗАБЛОКОВАНО' . R
            : (($row['ageSec'] <= 300) ? GREEN . '● ОНЛАЙН' . R : GRAY . '○ offline');
        out(CYAN . '  ┌─────────────────────────────────────────────────┐');
        out(CYAN . '  │  ' . B . str_pad('IP: ' . $ip, 45) . R . CYAN . ' │');
        out(CYAN . '  │  ' . GRAY . str_pad('Статус: ' . $statusStr, 60) . CYAN . '  │');
        out(CYAN . '  │  ' . GRAY . 'Активність: ' . $ageStr . ', Людськ: ' . $row['human'] . '/12'
            . ', Підозра: ' . $row['suspect'] . str_repeat(' ', 10) . CYAN . '│');
        out(CYAN . '  └─────────────────────────────────────────────────┘');
        out('');
        out('    ' . B . '1' . R . '  Забанити ' . YEL . '30 днів');
        out('    ' . B . '2' . R . '  Забанити ' . RED . 'назавжди');
        out('    ' . B . '3' . R . ($isBanned ? '  ' . GREEN . 'Розбанити' : '  ' . GRAY . 'Розбанити (не забанен)'));
        out('    ' . B . '4' . R . ($isWl ? '  ' . GREEN . 'Вже у whitelist' : '  Додати у whitelist'));
        out('    ' . B . '5' . R . ($isWl ? '  Видалити з whitelist' : '  ' . GRAY . 'Видалити з whitelist (немає)'));
        out('    ' . B . '0' . R . '  Назад');
        out('');
        $act = trim((string)($ogAsk)('  Дія', '0'));
        if ($act === '1') {
            og_patch_cli_ban_ip($offerPath, $dataDir, $ip, 86400 * 30);
        } elseif ($act === '2') {
            og_patch_cli_ban_ip($offerPath, $dataDir, $ip, 86400 * 365 * 10);
        } elseif ($act === '3') {
            og_patch_cli_unban_ip($offerPath, $dataDir, $ip);
        } elseif ($act === '4') {
            og_patch_cli_allow_ip($offerPath, $dataDir, $ip);
        } elseif ($act === '5') {
            og_patch_cli_deny_ip($offerPath, $dataDir, $ip);
        }
        if ($act !== '0') {
            fwrite(STDOUT, "\n  " . GRAY . 'Натисніть Enter…' . R);
            fgets(STDIN);
        }
        goto OG_BANS_SCREEN;
    }

    // IP entered directly
    if (filter_var($input, FILTER_VALIDATE_IP)) {
        $ip = $input;
        og_patch_cli_clear_screen();
        // Find existing state for this IP
        $found = null;
        foreach ($states as $d) {
            if ($d['ip'] === $ip) { $found = $d; break; }
        }
        $isBanned = $found !== null && (
            (!empty($found['atk_block']) && $found['atk_block'] > $now) ||
            (!empty($found['rl_block'])  && $found['rl_block']  > $now)
        ) || isset($permBans[$ip]);
        $isWl = isset($whitelist[$ip]);
        out('');
        out(CYAN . '  ┌──────────────────────────────────┐');
        out(CYAN . '  │  ' . B . "IP: $ip" . R . CYAN . '│');
        out(CYAN . '  └──────────────────────────────────┘');
        out('    ' . B . '1' . R . '  Забанити ' . YEL . '30 днів');
        out('    ' . B . '2' . R . '  Забанити ' . RED . 'назавжди');
        out('    ' . B . '3' . R . ($isBanned ? '  ' . GREEN . 'Розбанити' : '  ' . GRAY . 'Розбанити (не забанен)'));
        out('    ' . B . '4' . R . ($isWl ? '  ' . GREEN . 'Вже у whitelist' : '  Додати у whitelist'));
        out('    ' . B . '0' . R . '  Назад');
        out('');
        $act = trim((string)($ogAsk)('  Дія', '0'));
        if ($act === '1') { og_patch_cli_ban_ip($offerPath, $dataDir, $ip, 86400 * 30); }
        elseif ($act === '2') { og_patch_cli_ban_ip($offerPath, $dataDir, $ip, 86400 * 365 * 10); }
        elseif ($act === '3') { og_patch_cli_unban_ip($offerPath, $dataDir, $ip); }
        elseif ($act === '4') { og_patch_cli_allow_ip($offerPath, $dataDir, $ip); }
        if ($act !== '0') {
            fwrite(STDOUT, "\n  " . GRAY . 'Натисніть Enter…' . R);
            fgets(STDIN);
        }
        goto OG_BANS_SCREEN;
    }

    // Unknown input
    warn("Невідомий ввід: $input");
    fwrite(STDOUT, "\n  " . GRAY . 'Натисніть Enter…' . R);
    fgets(STDIN);
    goto OG_BANS_SCREEN;
}


function og_patch_cli_allow_ip(string $offerPath, string $dataDir, string $allowIp): bool
{
    head("ВАЙТЛИСТ — ДОБАВИТЬ: $allowIp");
    if (!filter_var($allowIp, FILTER_VALIDATE_IP)) {
        fail("Некорректный IP-адрес: $allowIp");
        out('');
        return false;
    }
    if (!is_dir($dataDir)) @mkdir($dataDir, 0700, true);
    $wlFile = $dataDir . '/whitelist.txt';
    $list   = is_file($wlFile) ? (file($wlFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []) : [];
    $list   = array_map('trim', $list);
    if (!in_array($allowIp, $list, true)) {
        $list[] = $allowIp;
        file_put_contents($wlFile, implode("\n", $list) . "\n");
        ok("Добавлен в вайтлист: $allowIp");
    } else {
        warn("Уже в вайтлисте: $allowIp");
    }
    $changed = og_clear_ip_ban_state_all($offerPath, $dataDir, $allowIp);
    ok("Очищены active ban/suspect/live/preflight флаги: $allowIp");
    if ($changed['perm']) ok("Удалён из перманентного бан-листа: $allowIp");
    out('');
    return true;
}


function og_patch_cli_deny_ip(string $offerPath, string $dataDir, string $denyIp): bool
{
    head("ВАЙТЛИСТ — УДАЛИТЬ: $denyIp");
    if (!filter_var($denyIp, FILTER_VALIDATE_IP)) {
        fail("Некорректный IP-адрес: $denyIp");
        out('');
        return false;
    }
    $wlFile = $dataDir . '/whitelist.txt';
    if (!is_file($wlFile)) {
        warn('Вайтлист уже пуст');
    } else {
        $list = file($wlFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $list = array_map('trim', $list);
        $new  = array_values(array_filter($list, fn($x) => $x !== $denyIp));
        if (count($new) < count($list)) {
            file_put_contents($wlFile, $new ? implode("\n", $new) . "\n" : '');
            ok("Удалён из вайтлиста: $denyIp");
        } else {
            warn("IP не найден в вайтлисте: $denyIp");
        }
    }
    out('');
    return true;
}


function og_patch_interactive_setup_protect(string $offerPath, array &$args, callable $ogAsk): bool
{
    $ogDetHost = '';
    try {
        $ogDetHost = og_patch_detect_canonical_host_from_paths($offerPath, (string)(getcwd() ?: ''));
        if ($ogDetHost === '') {
            $ogDetHost = og_patch_read_canonical_from_bot_protect($offerPath);
        }
    } catch (Throwable $e) {}
    $ogHostIn = og_patch_prompt_canonical_host($ogDetHost, $ogAsk, true);
    if ($ogHostIn === '' || !preg_match('/^[a-z0-9.-]+(:\d+)?$/', $ogHostIn)) {
        fail('Домен не задано — макс-захист потребує домену.');
        return false;
    }
    out('');
    out(B . '  Буде увімкнено МАКСИМАЛЬНИЙ захист на: ' . YEL . $ogHostIn);
    out(GRAY . '  (encrypt-body/js, split-key, runtime-ob, copy-kill, cloak, strict)');
    $ogYes = strtolower($ogAsk('Продовжити? (y/n)', 'y'));
    if ($ogYes !== 'y' && $ogYes !== 'yes' && $ogYes !== 'д' && $ogYes !== 'да') {
        out(GRAY . '  Скасовано.');
        return false;
    }
    $args[] = '--canonical-host=' . $ogHostIn;
    out(GRAY . "\n  Активую захист…\n");
    return true;
}

/**
 * XLog submenu. Returns whether to run xlog viewer/tail and with which options.
 *
 * @return array{run: bool, tail: bool, level: ?string, filter: ?string, limit: int}
 */
function og_patch_cli_xlog_interactive(string $dataDir, string $protectDest, callable $ogAsk, int $defaultLimit = 50): array
{
    $xlogPath = $dataDir . '/og_xlog.log';
    $out = ['run' => false, 'tail' => false, 'level' => null, 'filter' => null, 'limit' => $defaultLimit];

    while (true) {
        og_patch_cli_clear_screen();
        out('');
        out('  ' . CYAN . B . '┌─ XLog — gate · decrypt · assets · JS-помилки ' . R . CYAN . str_repeat('─', max(0, 22)) . '┐' . R);
        $xlSz = is_file($xlogPath) ? (int)@filesize($xlogPath) : 0;
        out('  ' . CYAN . '│' . R . GRAY . '  файл: ' . basename($xlogPath) . R);
        $xlHuman = '0 B';
        if ($xlSz > 0) {
            if ($xlSz < 1024) {
                $xlHuman = (string)$xlSz . ' B';
            } elseif ($xlSz < 1048576) {
                $xlHuman = round($xlSz / 1024, 1) . ' KB';
            } else {
                $xlHuman = round($xlSz / 1048576, 2) . ' MB';
            }
        }
        out('  ' . CYAN . '│' . R . GRAY . '  розмір: ' . $xlHuman . R);
        $cntDbg = $cntInf = $cntWrn = $cntErr = $cntFat = 0;
        $cntSrv = $cntCli = 0;
        if (is_file($xlogPath) && $xlSz > 0) {
            $fh = @fopen($xlogPath, 'r');
            if ($fh) {
                $start = max(0, $xlSz - 500000);
                if ($start > 0) {
                    fseek($fh, $start);
                }
                while (($l = fgets($fh)) !== false) {
                    if (strpos($l, '|DEBUG|') !== false) {
                        $cntDbg++;
                    } elseif (strpos($l, '|INFO|') !== false) {
                        $cntInf++;
                    } elseif (strpos($l, '|WARN|') !== false) {
                        $cntWrn++;
                    } elseif (strpos($l, '|ERROR|') !== false) {
                        $cntErr++;
                    } elseif (strpos($l, '|FATAL|') !== false) {
                        $cntFat++;
                    }
                    if (strpos($l, '|srv|') !== false) {
                        $cntSrv++;
                    } elseif (strpos($l, '|cli|') !== false) {
                        $cntCli++;
                    }
                }
                fclose($fh);
            }
        }
        $stats = GRAY . 'DBG ' . R . $cntDbg . GRAY . '  INF ' . R . CYAN . $cntInf . R
            . GRAY . '  WRN ' . R . YEL . $cntWrn . R
            . GRAY . '  ERR ' . R . RED . $cntErr . R
            . GRAY . '  FAT ' . R . B . RED . $cntFat . R
            . GRAY . '   │   srv ' . R . $cntSrv . GRAY . '  cli ' . R . $cntCli;
        out('  ' . CYAN . '│' . R . '  ' . $stats);
        out('  ' . CYAN . '└' . str_repeat('─', 68) . '┘' . R);
        out('');
        out('  ' . B . ' 1 ' . R . GREEN . '  Live tail' . R . GRAY . '     ' . CYAN . '--xlog-tail' . R);
        out('  ' . B . ' 2 ' . R . CYAN  . '  Останні 50' . R . GRAY . '    ' . CYAN . '--xlog' . R);
        out('  ' . B . ' 3 ' . R . YEL   . '  Tail + рівень' . R . GRAY . '  ' . CYAN . '--xlog-tail --xlog-level=warn' . R);
        out('  ' . B . ' 4 ' . R . MAG   . '  Tail + fn' . R . GRAY . '      ' . CYAN . '--xlog-tail --xlog-filter=fn=…' . R);
        out('  ' . B . ' 5 ' . R . RED   . '  Лише ERR+FATAL' . R . GRAY . '  ' . CYAN . '--xlog --xlog-level=error' . R);
        out('  ' . B . ' 6 ' . R . CYAN  . '  Рівень у bot-protect.php' . R . GRAY . '  (xlog_level)' . R);
        out('  ' . B . ' 7 ' . R . GRAY  . '  Очистити' . R . GRAY . '           ' . CYAN . '--xlog-clear' . R);
        out('  ' . B . ' 0 ' . R . GRAY  . '  Назад' . R);
        out('');
        $xc = trim((string)$ogAsk('  >', '1'));
        if ($xc === '0') {
            return $out;
        }
        if ($xc === '1') {
            $out = ['run' => true, 'tail' => true, 'level' => null, 'filter' => null, 'limit' => $defaultLimit];
            return $out;
        }
        if ($xc === '2') {
            $out = ['run' => true, 'tail' => false, 'level' => null, 'filter' => null, 'limit' => $defaultLimit];
            return $out;
        }
        if ($xc === '3') {
            $lvl = strtolower(trim((string)$ogAsk('  Рівень (debug|info|warn|error|fatal)', 'warn')));
            if (!in_array($lvl, ['debug', 'info', 'warn', 'error', 'fatal'], true)) {
                warn('Невірний рівень — використовую warn.');
                $lvl = 'warn';
            }
            $out = ['run' => true, 'tail' => true, 'level' => $lvl, 'filter' => null, 'limit' => $defaultLimit];
            return $out;
        }
        if ($xc === '4') {
            $fn = trim((string)$ogAsk('  Підрядок (напр. _ogDecryptPayload)'));
            if ($fn === '') {
                warn('Порожній фільтр.');
                continue;
            }
            $out = ['run' => true, 'tail' => true, 'level' => null, 'filter' => $fn, 'limit' => $defaultLimit];
            return $out;
        }
        if ($xc === '5') {
            $out = ['run' => true, 'tail' => false, 'level' => 'error', 'filter' => null, 'limit' => 100];
            return $out;
        }
        if ($xc === '6') {
            if (!is_file($protectDest)) {
                warn('bot-protect.php не знайдено — спочатку пункт 1 головного меню.');
                out(GRAY . '  Enter…' . R);
                fgets(STDIN);
                continue;
            }
            out('');
            $bpRaw = (string)@file_get_contents($protectDest);
            $curLvl = 'info';
            if (preg_match("/'xlog_level'\s*=>\s*'([^']+)'/", $bpRaw, $cmL)) {
                $curLvl = strtolower(trim($cmL[1]));
            }
            out(GRAY . '  Поточний xlog_level: ' . R . B . CYAN . $curLvl . R);
            $newLvl = strtolower(trim((string)$ogAsk('  Новий (debug|info|warn|error|fatal)', $curLvl)));
            if (!in_array($newLvl, ['debug', 'info', 'warn', 'error', 'fatal'], true)) {
                warn('Невірний рівень.');
            } elseif ($newLvl !== $curLvl) {
                $newRaw = preg_replace("/'xlog_level'\s*=>\s*'[^']+'/", "'xlog_level' => '" . $newLvl . "'", $bpRaw, 1);
                if ($newRaw !== null && $newRaw !== $bpRaw) {
                    @file_put_contents($protectDest, $newRaw, LOCK_EX);
                    ok("xlog_level: $curLvl → $newLvl");
                } else {
                    warn('Рядок xlog_level не знайдено — перепатчіть (пункт 1).');
                }
            } else {
                info('Без змін.');
            }
            out(GRAY . '  Enter…' . R);
            fgets(STDIN);
            continue;
        }
        if ($xc === '7') {
            if (is_file($xlogPath)) {
                @unlink($xlogPath);
                ok('Очищено: ' . $xlogPath);
            } else {
                info('Файл відсутній.');
            }
            out(GRAY . '  Enter…' . R);
            fgets(STDIN);
            continue;
        }
        warn('Оберіть 0–7.');
        out(GRAY . '  Enter…' . R);
        fgets(STDIN);
    }
}

/** Статичний знімок логів (без live). */
function og_patch_cli_monitoring_snapshot(string $offerPath, string $dataDir, int $lines = 80): void
{
    og_patch_cli_clear_screen();
    og_patch_cli_show_logs($offerPath, $lines);
    out(GRAY . '  Enter — назад у меню…' . R);
    fgets(STDIN);
}

/** @return list<array{tab:int,id:string,c:string,title:string,hint:string,flag:string}> */
function og_patch_cli_main_menu_items(): array
{
    return [
        ['tab' => 0, 'id' => '1',  'c' => GREEN, 'title' => 'Активувати / оновити захист', 'hint' => 'домен, макс-режим', 'flag' => '--patch'],
        ['tab' => 0, 'id' => '2',  'c' => CYAN,  'title' => 'Перевірити копію', 'hint' => 'мертва / жива', 'flag' => '--verify-copy'],
        ['tab' => 0, 'id' => '3',  'c' => CYAN,  'title' => 'Статус захисту', 'hint' => 'прапорці, бани', 'flag' => '--status'],
        ['tab' => 1, 'id' => '4',  'c' => RED,   'title' => 'Бани / онлайн / unban', 'hint' => 'інтерактив', 'flag' => '--bans'],
        ['tab' => 1, 'id' => '5',  'c' => CYAN,  'title' => 'Whitelist', 'hint' => '--allow / --deny', 'flag' => '--whitelist'],
        ['tab' => 1, 'id' => '17', 'c' => YEL,   'title' => 'Чому IP заблокований', 'hint' => '--why IP', 'flag' => '--why'],
        ['tab' => 2, 'id' => '6',  'c' => YEL,   'title' => 'Live-мониторинг', 'hint' => 'логи в реальному часі', 'flag' => 'live'],
        ['tab' => 2, 'id' => '16', 'c' => CYAN,  'title' => 'Sessions', 'hint' => 'завершені сесії', 'flag' => '--sessions'],
        ['tab' => 3, 'id' => '9',  'c' => MAG,   'title' => 'Doctor', 'hint' => 'права, htaccess', 'flag' => '--doctor'],
        ['tab' => 3, 'id' => '12', 'c' => GREEN, 'title' => 'Verify патча', 'hint' => 'коректність', 'flag' => '--verify'],
        ['tab' => 3, 'id' => '18', 'c' => CYAN,  'title' => 'Site-up', 'hint' => 'secret, chmod', 'flag' => '--site-up'],
        ['tab' => 4, 'id' => '7',  'c' => GRAY,  'title' => 'Rollback', 'hint' => '_og_backup', 'flag' => '--rollback'],
        ['tab' => 4, 'id' => '8',  'c' => GRAY,  'title' => 'Cleanup', 'hint' => 'зняти OfferGuard', 'flag' => '--cleanup'],
        ['tab' => 4, 'id' => '10', 'c' => RED,   'title' => 'Recovery', 'hint' => 'сайт не відкривається', 'flag' => '--recovery'],
        ['tab' => 4, 'id' => '11', 'c' => YEL,   'title' => 'Reset state', 'hint' => 'бани, логи', 'flag' => '--reset-state'],
    ];
}

/**
 * Головне меню: вкладки ←→, пункти ↑↓, Enter / цифра.
 *
 * @param array<string, mixed> $st
 */
function og_patch_cli_main_menu_interactive(array $st): string
{
    if (!og_patch_cli_is_tty()) {
        fwrite(STDOUT, "\n  " . B . CYAN . '>' . R . ' Оберіть пункт [1]: ');
        $v = fgets(STDIN);
        return trim($v === false ? '1' : $v) ?: '1';
    }

    $tabLabels = ['Захист', 'Доступ', 'Монітор', 'Діагн', 'Сервіс'];
    $tabCount = count($tabLabels);
    $allItems = og_patch_cli_main_menu_items();
    $activeTab = 0;
    $selected = 0;
    $running = true;
    $resizePending = false;
    $lastRows = 0;
    $lastCols = 0;
    $choice = '1';

    $itemsForTab = static function (int $tab) use ($allItems): array {
        return array_values(array_filter($allItems, static fn($it) => $it['tab'] === $tab));
    };

    if (function_exists('pcntl_async_signals')) {
        pcntl_async_signals(true);
    }
    if (function_exists('pcntl_signal')) {
        pcntl_signal(SIGINT, static function () use (&$running): void { $running = false; $choice = '0'; });
        if (defined('SIGWINCH')) {
            pcntl_signal(SIGWINCH, static function () use (&$resizePending): void { $resizePending = true; });
        }
    }

    $draw = static function () use (
        &$st, $tabLabels, $tabCount, &$activeTab, &$selected, $itemsForTab, &$lastRows, &$lastCols
    ): void {
        [$rows, $cols] = og_patch_cli_term_size();
        $lastRows = $rows;
        $lastCols = $cols;
        $sep = str_repeat('─', max(20, $cols - 2));
        $items = $itemsForTab($activeTab);
        if ($selected >= count($items)) {
            $selected = max(0, count($items) - 1);
        }

        echo "\033[H\033[2J\033[J\033[?25l";

        $host = (string)($st['host'] ?? '');
        $bpOk = !empty($st['bpHere']);
        $permOk = !empty($st['permOk']);
        $bans = (int)($st['bans'] ?? 0);
        $online = (int)($st['online'] ?? 0);
        $xErr = (int)($st['xErr'] ?? 0);

        out('');
        foreach (og_patch_cli_logo_lines($cols, $rows) as $logoLn) {
            out($logoLn);
        }
        $st = [];
        if ($host !== '') {
            $st[] = CYAN . B . $host . R;
        }
        $st[] = $bpOk ? GREEN . 'ON' . R : RED . 'OFF' . R;
        $st[] = $permOk ? GREEN . 'data OK' . R : RED . 'data WARN' . R;
        if ($bans > 0) {
            $st[] = RED . 'ban ' . $bans . R;
        }
        if ($online > 0) {
            $st[] = GREEN . 'online ' . $online . R;
        }
        if ($xErr > 0) {
            $st[] = RED . 'xlog ' . $xErr . R;
        }
        $st[] = GRAY . $rows . 'x' . $cols . R;
        out(og_patch_cli_center(implode(GRAY . ' | ' . R, $st), $cols));

        out(og_patch_cli_tab_bar($tabLabels, $activeTab));
        out(GRAY . '  ' . $sep . R);

        $logoRowsMenu = count(og_patch_cli_logo_lines($cols, $rows)) + 2;
        $bodyMax = max(4, $rows - $logoRowsMenu - 5);
        $shown = 0;
        foreach ($items as $idx => $it) {
            if ($shown >= $bodyMax) {
                out(GRAY . '  ... ще ' . (count($items) - $shown) . ' (Down)' . R);
                break;
            }
            og_patch_cli_menu_line($it, $idx === $selected, $cols);
            $shown++;
        }

        out(GRAY . '  ' . $sep . R);
        out(GRAY . '  Up/Down: пункт   Left/Right: вкладка   Enter   0: вихід   цифра: швидкий вибір' . R);
        $path = (string)($st['path'] ?? '');
        if (strlen($path) > $cols - 4) {
            $path = '…' . substr($path, -($cols - 5));
        }
        out(GRAY . '  ' . $path . R);
    };

    $GLOBALS['ogMenuTuiActive'] = true;
    og_patch_cli_tty_raw(true);
    $draw();

    while ($running) {
        [$curRows, $curCols] = og_patch_cli_term_size();
        if ($resizePending || $curRows !== $lastRows || $curCols !== $lastCols) {
            $resizePending = false;
            $draw();
        }

        $key = og_patch_cli_read_nav_key(150000);
        $items = $itemsForTab($activeTab);

        if ($key === 'quit' || $key === 'digit0') {
            $choice = '0';
            $running = false;
            break;
        }
        if ($key === 'left') {
            $activeTab = ($activeTab - 1 + $tabCount) % $tabCount;
            $selected = 0;
            $draw();
        } elseif ($key === 'right') {
            $activeTab = ($activeTab + 1) % $tabCount;
            $selected = 0;
            $draw();
        } elseif ($key === 'up') {
            if ($items !== []) {
                $selected = ($selected - 1 + count($items)) % count($items);
            }
            $draw();
        } elseif ($key === 'down') {
            if ($items !== []) {
                $selected = ($selected + 1) % count($items);
            }
            $draw();
        } elseif ($key === 'enter' && $items !== []) {
            $choice = $items[$selected]['id'];
            $running = false;
            break;
        } elseif ($key !== null && str_starts_with($key, 'digit')) {
            $d = substr($key, 5);
            foreach ($allItems as $it) {
                if ($it['id'] === $d) {
                    $choice = $d;
                    $running = false;
                    break 2;
                }
            }
        } elseif ($key !== null && str_starts_with($key, 'tab')) {
            $activeTab = max(0, min($tabCount - 1, (int)substr($key, 3) - 1));
            $selected = 0;
            $draw();
        }

        if (function_exists('pcntl_signal_dispatch')) {
            pcntl_signal_dispatch();
        }
    }

    echo "\033[?25h";
    og_patch_cli_tty_raw(false);
    unset($GLOBALS['ogMenuTuiActive']);
    return $choice;
}


OG_INTERACTIVE_MENU:
if (!empty($ogInteractiveNeedMenu)) {
    if (!empty($ogInteractivePauseBeforeMenu)) {
        og_patch_cli_pause_and_clear();
        $ogInteractivePauseBeforeMenu = false;
    } else {
        og_patch_cli_clear_screen();
    }
    $ogInteractiveNeedMenu = false;

    // ── Gather state ───────────────────────────────────────────────
    $bpHere     = file_exists($protectDest);
    $dataHere   = is_dir($dataDir);
    $bkupHere   = is_dir($backupDir);
    $mnNow      = time();
    $bansCount  = 0;
    $onlineCount = 0;
    $wlCount    = 0;
    if (is_dir($dataDir)) {
        $wlFile = $dataDir . '/whitelist.txt';
        if (is_file($wlFile)) {
            $wlCount = count(array_filter(file($wlFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [], 'trim'));
        }
        foreach (glob($dataDir . '/*.json') ?: [] as $jf) {
            if (!preg_match('/^[a-f0-9]{32}\.json$/', basename($jf))) { continue; }
            $jd = json_decode((string)@file_get_contents($jf), true) ?: [];
            if ((int)($jd['atk_block'] ?? 0) > $mnNow || (int)($jd['rl_block'] ?? 0) > $mnNow) { $bansCount++; }
            $jTs = is_array($jd['ts'] ?? null) ? $jd['ts'] : [];
            $jLast = $jTs ? max($jTs) : 0;
            if ($jLast > 0 && ($mnNow - $jLast) <= 300) { $onlineCount++; }
        }
    }
    $hostStr = '';
    try {
        $bp = is_file($protectDest) ? (string)@file_get_contents($protectDest) : '';
        if (preg_match("/'canonical_host'\s*=>\s*'([^']+)'/", $bp, $hm) && $hm[1] !== 'OG_CANONICAL_HOST_CHANGE_ME') {
            $hostStr = $hm[1];
        }
    } catch (\Throwable $e) {}

    // ── XLog статистика для бейджей ────────────────────────────────
    $ogXlogFile = $dataDir . '/og_xlog.log';
    $ogXlogErrCount = 0;
    $ogXlogWarnCount = 0;
    $ogXlogTotal = 0;
    if (is_file($ogXlogFile)) {
        $ogXlogSize = (int)@filesize($ogXlogFile);
        $fh = @fopen($ogXlogFile, 'r');
        if ($fh) {
            $startOff = max(0, $ogXlogSize - 200000);
            if ($startOff > 0) fseek($fh, $startOff);
            while (($ln = fgets($fh)) !== false) {
                if (trim($ln) === '') continue;
                $ogXlogTotal++;
                if (strpos($ln, '|ERROR|') !== false || strpos($ln, '|FATAL|') !== false) $ogXlogErrCount++;
                elseif (strpos($ln, '|WARN|') !== false) $ogXlogWarnCount++;
            }
            fclose($fh);
        }
    }

    $permFix = og_patch_fix_og_data_writable($offerPath, og_patch_resolve_web_owner($offerPath));
    if (!$permFix['fixed'] && function_exists('posix_geteuid') && posix_geteuid() !== 0) {
        warn('_og_data не writable — спробуй: sudo php patch.php ' . $offerPath);
    }

    $menuState = [
        'host' => $hostStr,
        'bpHere' => $bpHere,
        'bans' => $bansCount,
        'online' => $onlineCount,
        'wl' => $wlCount,
        'xErr' => $ogXlogErrCount,
        'xWarn' => $ogXlogWarnCount,
        'permOk' => $permFix['fixed'],
        'path' => $offerPath,
    ];
    $ogChoice = og_patch_cli_main_menu_interactive($menuState);

    if ($ogChoice === '0') {
        out(GRAY . '  До побачення.');
        exit(0);
    } elseif ($ogChoice === '1') {
        if (!og_patch_interactive_setup_protect($offerPath, $args, $ogAsk)) {
            $ogInteractiveNeedMenu = true;
            $ogInteractivePauseBeforeMenu = true;
            goto OG_INTERACTIVE_MENU;
        }
    } elseif ($ogChoice === '2') {
        $verifyCopy = true;
    } elseif ($ogChoice === '3') {
        $showStatus = true;
    } elseif ($ogChoice === '4') {
        og_patch_cli_bans_interactive($offerPath, $dataDir, $ogAsk);
        $ogInteractiveNeedMenu = true;
        goto OG_INTERACTIVE_MENU;
    } elseif ($ogChoice === '5') {
        og_patch_cli_clear_screen();
        og_patch_cli_show_whitelist($offerPath, $dataDir);
        out('');
        out(GRAY . '  ' . str_repeat('─', 52));
        out('  ' . B . '1' . R . GRAY . '  Додати IP у whitelist');
        out('  ' . B . '2' . R . GRAY . '  Видалити IP з whitelist');
        out('  ' . B . '0' . R . GRAY . '  Назад');
        out('');
        $wlAct = trim((string)$ogAsk('  >', '0'));
        if ($wlAct === '1') {
            $ogAllowIn = trim((string)$ogAsk('  IP для додавання'));
            if ($ogAllowIn !== '') { og_patch_cli_allow_ip($offerPath, $dataDir, $ogAllowIn); }
        } elseif ($wlAct === '2') {
            $ogDenyIn = trim((string)$ogAsk('  IP для видалення'));
            if ($ogDenyIn !== '') { og_patch_cli_deny_ip($offerPath, $dataDir, $ogDenyIn); }
        }
        $ogInteractiveNeedMenu = true;
        $ogInteractivePauseBeforeMenu = true;
        goto OG_INTERACTIVE_MENU;
    } elseif ($ogChoice === '6' || $ogChoice === '13' || $ogChoice === '14' || $ogChoice === '15') {
        $startTab = 0;
        if ($ogChoice === '13') {
            $startTab = 1;
        } elseif ($ogChoice === '15') {
            $startTab = 2;
        }
        og_patch_cli_monitoring_live($offerPath, $dataDir, $startTab);
        $ogInteractiveNeedMenu = true;
        goto OG_INTERACTIVE_MENU;
    } elseif ($ogChoice === '7') {
        $rollback = true;
    } elseif ($ogChoice === '8') {
        $cleanup = true;
    } elseif ($ogChoice === '9') {
        $doctor = true;
    } elseif ($ogChoice === '10') {
        og_patch_cli_clear_screen();
        out('');
        out(RED . B . '  ┌─────────────────────────────────────────────────────┐');
        out(RED . B . '  │  ⚠  УВАГА: RECOVERY                                 │');
        out(RED . B . '  └─────────────────────────────────────────────────────┘' . R);
        out(GRAY . '  Видалить: bot-protect.php, OG-блоки з .htaccess, PHP-обгортки.');
        out(GRAY . '  Захист буде знятий. Після → Пункт 1 для нового патча.');
        out('');
        fwrite(STDOUT, '  ' . RED . 'Продовжити? [y/N]: ' . R);
        $ogRecAns = strtolower(trim((string)fgets(STDIN)));
        if (in_array($ogRecAns, ['y', 'yes', 'так', 'да', '1'], true)) {
            $recoveryMode = true;
        } else {
            warn('Recovery скасовано.');
            $ogInteractiveNeedMenu = true;
            $ogInteractivePauseBeforeMenu = true;
            goto OG_INTERACTIVE_MENU;
        }
    } elseif ($ogChoice === '11') {
        og_patch_cli_clear_screen();
        out('');
        out(YEL . B . '  ┌─────────────────────────────────────────────────────┐');
        out(YEL . B . '  │  ⚠  RESET STATE                                     │');
        out(YEL . B . '  └─────────────────────────────────────────────────────┘' . R);
        out(GRAY . '  Обнулить: ip-state, perm_ban, fingerprint-кеш, логи.');
        out(GREEN . '  ✓ whitelist.txt збережеться.');
        out('');
        fwrite(STDOUT, '  ' . YEL . 'Продовжити? [y/N]: ' . R);
        $ogRsAns = strtolower(trim((string)fgets(STDIN)));
        if (in_array($ogRsAns, ['y', 'yes', 'так', 'да', '1'], true)) {
            $resetStateMode = true;
        } else {
            warn('Reset state скасовано.');
            $ogInteractiveNeedMenu = true;
            $ogInteractivePauseBeforeMenu = true;
            goto OG_INTERACTIVE_MENU;
        }
    } elseif ($ogChoice === '12') {
        $verifyMode = true;
    } elseif ($ogChoice === '16') {
        $showSessions = true;
        $tLim = (int)trim((string)$ogAsk('  Кількість рядків (10–200)', '50'));
        if ($tLim < 10) $tLim = 50;
        if ($tLim > 200) $tLim = 200;
        $limitLines = $tLim;
    } elseif ($ogChoice === '17') {
        og_patch_cli_clear_screen();
        out('');
        out('  ' . B . CYAN . '┌─ ЧОМУ IP ЗАБЛОКОВАНИЙ ──────────────────────────┐' . R);
        out('  ' . CYAN . '│' . R . '  Показує: бани, причини, кеш гейтів, останній час.');
        out('  ' . CYAN . '└─────────────────────────────────────────────────┘' . R);
        out('');
        $wIp = trim((string)$ogAsk('  IP (напр. 85.158.110.225)'));
        if ($wIp === '' || !filter_var($wIp, FILTER_VALIDATE_IP)) {
            warn('Не вірний IP — повертаюсь у меню.');
            out(GRAY . '  Натисніть Enter…' . R);
            fgets(STDIN);
            $ogInteractiveNeedMenu = true;
            goto OG_INTERACTIVE_MENU;
        }
        $whyIp = $wIp;
    } elseif ($ogChoice === '18') {
        $siteUp = true;
    } else {
        warn('Невідомий пункт — оберіть 0–18.');
        $ogInteractiveNeedMenu = true;
        $ogInteractivePauseBeforeMenu = true;
        goto OG_INTERACTIVE_MENU;
    }
}


if ($xlogClearMode) {
    $xfile = $dataDir . '/og_xlog.log';
    if (is_file($xfile)) { @unlink($xfile); ok("Очищен: $xfile"); }
    else { info('Лог пустой, нечего чистить.'); }
    exit(0);
}

if ($xlogMode || $xlogTailMode) {
    $xfile = $dataDir . '/og_xlog.log';
    $tail = $xlogTailMode;
    $levelFilter = $xlogLevel ? strtoupper($xlogLevel) : null;
    $textFilter = $xlogFilter;
    $minLvlRank = $levelFilter !== null ? og_patch_xlog_level_rank($levelFilter) : 0;

    $lvlColor = static function (string $lvl): string {
        switch (strtoupper($lvl)) {
            case 'DEBUG': return GRAY;
            case 'INFO': return CYAN;
            case 'WARN': return YEL;
            case 'ERROR': return RED;
            case 'FATAL': return B . RED;
        }
        return '';
    };

    $formatCtx = static function (string $ctx): string {
        if ($ctx === '') {
            return '';
        }
        $j = json_decode($ctx, true);
        if (is_array($j)) {
            $pretty = json_encode($j, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if (is_string($pretty) && strlen($pretty) <= 220) {
                return $pretty;
            }
        }
        return strlen($ctx) > 220 ? substr($ctx, 0, 217) . '…' : $ctx;
    };

    $xlogHeaderPrinted = false;
    $printHeader = static function () use (&$xlogHeaderPrinted): void {
        if ($xlogHeaderPrinted) {
            return;
        }
        $xlogHeaderPrinted = true;
        out(B . GRAY . '  ' . str_pad('TIME', 13)
            . str_pad('LVL', 6)
            . str_pad('SRC', 4)
            . str_pad('RID', 9)
            . str_pad('IP', 16)
            . str_pad('URI', 26)
            . str_pad('FUNCTION', 20)
            . 'MESSAGE' . R);
        out(GRAY . '  ' . str_repeat('─', 118) . R);
    };

    $errSummary = [];
    $printLine = static function (string $line) use (
        $lvlColor, $levelFilter, $minLvlRank, $textFilter, $formatCtx, $printHeader, &$errSummary
    ): bool {
        $e = og_patch_parse_xlog_line($line);
        if ($e === null) {
            out(trim($line));
            return true;
        }
        if ($minLvlRank > 0 && og_patch_xlog_level_rank($e['lvl']) < $minLvlRank) {
            return false;
        }
        if ($textFilter !== null && stripos($e['raw'], $textFilter) === false) {
            return false;
        }

        $printHeader();

        $col = $lvlColor($e['lvl']);
        $tsShort = strlen($e['ts']) >= 19 ? substr($e['ts'], 11, 12) : $e['ts'];
        $uri = strlen($e['uri']) > 24 ? substr($e['uri'], 0, 23) . '…' : $e['uri'];
        $fn = strlen($e['fn']) > 18 ? substr($e['fn'], 0, 17) . '…' : $e['fn'];
        $msg = $e['msg'] !== '' ? $e['msg'] : '—';
        $isErr = in_array($e['lvl'], ['ERROR', 'FATAL'], true);

        out($col . str_pad($tsShort, 13)
            . str_pad($e['lvl'], 6)
            . ($e['src'] === 'cli' ? CYAN . B . 'cli' . R . $col : GRAY . 'srv' . R . $col)
            . ' '
            . str_pad($e['rid'], 8)
            . ' '
            . str_pad($e['ip'], 15)
            . ' '
            . str_pad($uri, 25)
            . ' '
            . str_pad($fn, 19)
            . ' '
            . ($isErr ? B . RED : '') . $msg . R);

        if ($e['ctx'] !== '' && in_array($e['lvl'], ['WARN', 'ERROR', 'FATAL'], true)) {
            $ctxOut = $formatCtx($e['ctx']);
            out(GRAY . '           └ ' . R . YEL . $ctxOut . R);
        }

        if ($isErr) {
            $errSummary[] = $e;
            if (count($errSummary) > 12) {
                array_shift($errSummary);
            }
        }

        return true;
    };

    if ($tail) {
        head("XLOG LIVE  (Ctrl+C для выхода)" . ($levelFilter ? "  level≥$levelFilter" : '') . ($textFilter ? "  filter=$textFilter" : ''));
        if (!is_file($xfile)) { warn("Лог пуст, ждём..."); }
        $offset = is_file($xfile) ? filesize($xfile) : 0;
        while (true) {
            clearstatcache(true, $xfile);
            $size = is_file($xfile) ? filesize($xfile) : 0;
            if ($size > $offset) {
                $fh = @fopen($xfile, 'r');
                if ($fh) {
                    fseek($fh, $offset);
                    while (($line = fgets($fh)) !== false) {
                        $printLine($line);
                    }
                    fclose($fh);
                }
                $offset = $size;
            } elseif ($size < $offset) {
                $offset = 0;
            }
            usleep(400000);
        }
    } else {
        head("XLOG (последние $limitLines строк)" . ($levelFilter ? "  level≥$levelFilter" : '') . ($textFilter ? "  filter=$textFilter" : ''));
        if (!is_file($xfile)) { warn("Лог пуст: $xfile"); exit(0); }
        $lines = @file($xfile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $scan = array_slice($lines, -max(50, $limitLines * 8));
        $shown = 0;
        $cap = $limitLines;
        foreach ($scan as $ln) {
            if ($shown >= $cap) {
                break;
            }
            if ($printLine($ln)) {
                $shown++;
            }
        }
        if ($errSummary !== []) {
            out('');
            out(RED . B . '  ── ПОМИЛКИ В ЦЬОМУ ЗРІЗІ (' . count($errSummary) . ') ──' . R);
            foreach ($errSummary as $er) {
                $tsShort = strlen($er['ts']) >= 19 ? substr($er['ts'], 11, 12) : $er['ts'];
                out(RED . '  ' . $tsShort . '  ' . B . $er['fn'] . R . RED . '  ' . $er['msg'] . R);
                if ($er['ctx'] !== '') {
                    out(GRAY . '    ' . $formatCtx($er['ctx']) . R);
                }
            }
        }
        $cntDbg = $cntInf = $cntWrn = $cntErr = 0;
        foreach ($scan as $ln) {
            $pe = og_patch_parse_xlog_line($ln);
            if ($pe === null) {
                continue;
            }
            switch ($pe['lvl']) {
                case 'DEBUG': $cntDbg++; break;
                case 'INFO': $cntInf++; break;
                case 'WARN': $cntWrn++; break;
                case 'ERROR':
                case 'FATAL': $cntErr++; break;
            }
        }
        out('');
        out(GRAY . '  Зріз: DBG ' . $cntDbg . '  INF ' . CYAN . $cntInf . R . GRAY
            . '  WRN ' . YEL . $cntWrn . R . GRAY . '  ERR ' . RED . $cntErr . R
            . GRAY . '  (показано ' . $shown . ' рядків)' . R);
        out(GRAY . "\n  Файл:        $xfile" . R);
        out(GRAY . "  Live-tail:   php patch.php $offerPath --xlog-tail [--xlog-level=warn|error] [--xlog-filter=_ogDecryptPayload]" . R);
        out(GRAY . "  Очистить:    php patch.php $offerPath --xlog-clear" . R);
    }
    exit(0);
}

if ($showTraffic) {
    $tlog = $dataDir . '/og_traffic.log';

    if ($tailMode) {
        
        head("TRAFFIC LIVE  (Ctrl+C для выхода)");
        out(GRAY . "  Фильтр IP: " . ($filterIp ?? 'все') . "   Класс: " . ($filterClass ?? 'все'));
        $cols = [
            str_pad('Время',     19), str_pad('IP',           17),
            str_pad('Класс',      8), str_pad('Susp', 5),
            str_pad('Метод', 5),      str_pad('URL', 50),
            str_pad('Тип',  7),       'Страниц',
        ];
        out(B . GRAY . "  " . implode(' ', $cols));
        out(GRAY . "  " . str_repeat('─', 124));

        if (!is_file($tlog)) { warn("Лог пуст, ждём..."); }
        $offset = is_file($tlog) ? filesize($tlog) : 0;
        while (true) {
            clearstatcache(true, $tlog);
            $size = is_file($tlog) ? filesize($tlog) : 0;
            if ($size > $offset) {
                $fh = fopen($tlog, 'r');
                fseek($fh, $offset);
                while (($line = fgets($fh)) !== false) {
                    $p = explode("\t", trim($line));
                    if (count($p) < 8) continue;
                    [$ts,$lip,$lclass,$susp,$lm,$luri,$ltype] = $p;
                    if ($filterIp    && $lip    !== $filterIp)    continue;
                    if ($filterClass && $lclass !== $filterClass) continue;
                    $col = match($lclass) {
                        'bot','crawler' => RED, 'suspect','proxy' => YEL,
                        default => GREEN
                    };
                    out($col . "  " .
                        str_pad($ts,    19) . ' ' .
                        str_pad($lip,   17) . ' ' .
                        str_pad($lclass, 8) . ' ' .
                        str_pad($susp,   5) . ' ' .
                        str_pad($lm,     5) . ' ' .
                        str_pad(substr($luri, 0, 50), 50) . ' ' .
                        str_pad($ltype,  7) . ' ' .
                        ($p[14] ?? '?')
                    );
                }
                fclose($fh);
                $offset = $size;
            }
            usleep(500000); 
        }
        exit(0);
    }

    
    head("TRAFFIC REPORT");

    if (!is_file($tlog)) {
        warn("Лог трафика не найден: $tlog");
        out(GRAY . "  (появится после первых запросов к офферу)");
        out("");
        exit(0);
    }

    $lines = file($tlog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $total = count($lines);

    
    if ($filterIp || $filterClass) {
        $lines = array_filter($lines, function($l) use ($filterIp, $filterClass) {
            $p = explode("\t", $l);
            if ($filterIp    && ($p[1] ?? '') !== $filterIp)    return false;
            if ($filterClass && ($p[2] ?? '') !== $filterClass) return false;
            return true;
        });
    }
    $lines = array_values(array_slice($lines, -$limitLines));

    
    $classes = $ips = $uris = $signals_all = [];
    foreach ($lines as $l) {
        $p = explode("\t", $l);
        if (count($p) < 10) continue;
        $classes[$p[2] ?? 'unknown'] = ($classes[$p[2] ?? 'unknown'] ?? 0) + 1;
        $ips[$p[1] ?? '']            = ($ips[$p[1] ?? ''] ?? 0) + 1;
        $uriKey = substr($p[5] ?? '', 0, 60);
        $uris[$uriKey]               = ($uris[$uriKey] ?? 0) + 1;
        foreach (explode(',', $p[17] ?? '') as $sig) {
            $sig = trim($sig);
            if ($sig && $sig !== '–') $signals_all[$sig] = ($signals_all[$sig] ?? 0) + 1;
        }
    }

    pstat("Всего строк в логе:", (string)$total);
    pstat("Показано (--limit):", count($lines) . " записей");
    if ($filterIp)    pstat("Фильтр IP:",    $filterIp);
    if ($filterClass) pstat("Фильтр класс:", $filterClass);

    
    out(CYAN . "\n  Распределение по классам:");
    $classColors = ['human'=>GREEN,'suspect'=>YEL,'bot'=>RED,'crawler'=>RED,'proxy'=>MAG];
    arsort($classes);
    $total_shown = array_sum($classes) ?: 1;
    foreach ($classes as $cls => $cnt) {
        $pct  = round($cnt / $total_shown * 100, 1);
        $bar  = str_repeat('█', (int)($pct / 2));
        $col  = $classColors[$cls] ?? GRAY;
        out($col . "  " . str_pad($cls, 10) . GRAY . str_pad((string)$cnt, 6) . "  " . str_pad($bar, 50) . " $pct%");
    }

    
    out(CYAN . "\n  Топ IP-адресов:");
    arsort($ips);
    $n = 0;
    foreach ($ips as $tip => $cnt) {
        if (++$n > 10) break;
        out(GRAY . "  " . str_pad($tip, 18) . YEL . $cnt . " запросов");
    }

    
    out(CYAN . "\n  Топ запрашиваемых URL:");
    arsort($uris);
    $n = 0;
    foreach ($uris as $turi => $cnt) {
        if (++$n > 10) break;
        out(GRAY . "  " . str_pad($turi, 62) . YEL . $cnt);
    }

    
    if ($signals_all) {
        out(CYAN . "\n  Топ сигналов подозрения:");
        arsort($signals_all);
        $n = 0;
        foreach ($signals_all as $sig => $cnt) {
            if (++$n > 10) break;
            out(GRAY . "  " . str_pad($sig, 40) . YEL . $cnt);
        }
    }

    
    out(CYAN . "\n  Последние " . count($lines) . " запросов:");
    $hdr = [
        str_pad('Время',19), str_pad('IP',17), str_pad('Класс',9),
        str_pad('Susp',5), str_pad('М',5), str_pad('URL',45),
        str_pad('Тип',7), str_pad('Стр',4), str_pad('p/m',7), 'Сигналы'
    ];
    out(B . GRAY . "  " . implode(' ', $hdr));
    out(GRAY . "  " . str_repeat('─', 128));
    foreach ($lines as $l) {
        $p = explode("\t", $l);
        if (count($p) < 12) continue;
        $col = match($p[2] ?? '') {
            'bot','crawler' => RED, 'suspect','proxy' => YEL, default => GRAY
        };
        $sigs = substr($p[17] ?? '–', 0, 30);
        out($col . "  " .
            str_pad($p[0]  ?? '', 19) . ' ' .
            str_pad($p[1]  ?? '', 17) . ' ' .
            str_pad($p[2]  ?? '', 9)  . ' ' .
            str_pad($p[3]  ?? '', 5)  . ' ' .
            str_pad($p[4]  ?? '', 5)  . ' ' .
            str_pad(substr($p[5] ?? '', 0, 45), 45) . ' ' .
            str_pad($p[6]  ?? '', 7)  . ' ' .
            str_pad($p[14] ?? '', 4)  . ' ' .
            str_pad($p[15] ?? '', 7)  . ' ' .
            $sigs
        );
    }
    out(GRAY . "\n  Файл: " . $tlog);
    out(GRAY . "  Подсказки:");
    out(GRAY . "  --tail              live-режим (обновляется каждые 0.5 сек)");
    out(GRAY . "  --ip 1.2.3.4        фильтр по IP");
    out(GRAY . "  --class bot         фильтр по классу: human|suspect|bot|crawler|proxy");
    out(GRAY . "  --limit 200         кол-во строк");
    out("");
    exit(0);
}




if ($showSessions) {
    $slog = $dataDir . '/og_sessions.log';
    head("SESSIONS REPORT");

    if (!is_file($slog)) {
        warn("Лог сессий не найден: $slog");
        out(GRAY . "  (появится когда сессии начнут завершаться — через 30 мин неактивности)");
        out("");
        exit(0);
    }

    $lines = file($slog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    if ($filterIp || $filterClass) {
        $lines = array_filter($lines, function($l) use ($filterIp, $filterClass) {
            $p = explode("\t", $l);
            if ($filterIp    && ($p[1] ?? '') !== $filterIp)    return false;
            if ($filterClass && ($p[2] ?? '') !== $filterClass) return false;
            return true;
        });
    }
    $lines   = array_values(array_slice(array_values($lines), -$limitLines));
    $total   = count(file($slog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []);

    pstat("Всего сессий в логе:", (string)$total);
    pstat("Показано:", count($lines) . " сессий");

    
    $cls_cnt = []; $bot_ips = [];
    $avg_pages = $avg_spd = $avg_dur = 0;
    $cnt_parsed = 0;
    foreach ($lines as $l) {
        $p = explode("\t", $l);
        if (count($p) < 10) continue;
        $cls = $p[2] ?? 'human';
        $cls_cnt[$cls] = ($cls_cnt[$cls] ?? 0) + 1;
        if (in_array($cls, ['bot','crawler','suspect'])) $bot_ips[$p[1]] = true;
        $avg_pages += (int)($p[4] ?? 0);
        $avg_dur   += (int)rtrim($p[7] ?? '0', 's');
        $avg_spd   += (float)rtrim($p[8] ?? '0', 'p/m');
        $cnt_parsed++;
    }
    if ($cnt_parsed > 0) {
        pstat("Ср. страниц/сессию:", (string)round($avg_pages / $cnt_parsed, 1));
        pstat("Ср. длительность:",  round($avg_dur   / $cnt_parsed) . 'с');
        pstat("Ср. скорость:",      round($avg_spd   / $cnt_parsed, 2) . ' стр/мин');
        pstat("Подозрительных IP:", (string)count($bot_ips));
    }

    
    out(CYAN . "\n  Распределение по классам:");
    arsort($cls_cnt);
    $tot2 = max(1, array_sum($cls_cnt));
    $classColors = ['human'=>GREEN,'suspect'=>YEL,'bot'=>RED,'crawler'=>RED,'proxy'=>MAG];
    foreach ($cls_cnt as $cls => $cnt2) {
        $pct = round($cnt2 / $tot2 * 100, 1);
        $bar = str_repeat('█', (int)($pct / 2));
        out(($classColors[$cls] ?? GRAY) . "  " . str_pad($cls, 10) . GRAY . str_pad((string)$cnt2, 6) . "  $bar $pct%");
    }

    
    out(CYAN . "\n  Последние " . count($lines) . " сессий:");
    $hdr2 = [
        str_pad('Завершена',19), str_pad('IP',17), str_pad('Класс',9),
        str_pad('Susp',5), str_pad('Стр',4), str_pad('HTML',5),
        str_pad('CSS/JS',7), str_pad('Длит',7), str_pad('p/m',6),
        str_pad('Шрф',4), str_pad('FkRef',5), str_pad('Энтр',5),
        str_pad('ACF',5), str_pad('Сессий',7), 'Сигналы'
    ];
    out(B . GRAY . "  " . implode(' ', $hdr2));
    out(GRAY . "  " . str_repeat('─', 130));

    foreach ($lines as $l) {
        $p = explode("\t", $l);
        if (count($p) < 15) continue;
        $col = match($p[2] ?? '') {
            'bot','crawler' => RED, 'suspect','proxy' => YEL, default => GRAY
        };
        $sigs2 = substr($p[18] ?? '–', 0, 35);
        out($col . "  " .
            str_pad($p[0]  ?? '', 19) . ' ' .
            str_pad($p[1]  ?? '', 17) . ' ' .
            str_pad($p[2]  ?? '', 9)  . ' ' .
            str_pad($p[3]  ?? '', 5)  . ' ' .
            str_pad($p[4]  ?? '', 4)  . ' ' .
            str_pad($p[5]  ?? '', 5)  . ' ' .
            str_pad($p[6]  ?? '', 7)  . ' ' .
            str_pad($p[7]  ?? '', 7)  . ' ' .
            str_pad($p[8]  ?? '', 6)  . ' ' .
            str_pad($p[9]  ?? '', 4)  . ' ' .
            str_pad($p[10] ?? '', 5)  . ' ' .
            str_pad($p[12] ?? '', 5)  . ' ' .
            str_pad($p[13] ?? '', 5)  . ' ' .
            str_pad($p[15] ?? '', 7)  . ' ' .
            $sigs2
        );
    }
    out(GRAY . "\n  Файл: " . $slog);
    out(GRAY . "  --ip 1.2.3.4        фильтр по IP");
    out(GRAY . "  --class crawler     фильтр по классу");
    out(GRAY . "  --limit 100         кол-во сессий");
    out("");
    exit(0);
}




if ($verifyCopy) {
    head("VERIFY-COPY — будет ли работать копия");
    $vcFiles = [];
    $vcPeek = static function (string $f): string {
        return (string)@file_get_contents($f, false, null, 0, 98304);
    };
    $vcGlobs = [
        '*.html', '*.htm', '*.blade.php', '*.twig', '*.jinja2', '*.jsp', '*.jspf',
        '*.cshtml', '*.aspx', '*.asp', '*.ejs', '*.hbs', '*.mustache',
        '*.vue', '*.tsx', '*.jsx', '*.py',
    ];
    foreach (array_merge($vcGlobs, ['*.php']) as $g) {
        foreach (glob($offerPath . '/' . $g) ?: [] as $f) {
            if (basename($f) === 'bot-protect.php') {
                continue;
            }
            if (!og_patch_is_html_like_file($f, $vcPeek($f))) {
                continue;
            }
            $vcFiles[] = $f;
        }
        foreach (glob($offerPath . '/*/' . $g) ?: [] as $f) {
            if (strpos($f, '/_og_') === false && basename($f) !== 'bot-protect.php') {
                if (!og_patch_is_html_like_file($f, $vcPeek($f))) {
                    continue;
                }
                $vcFiles[] = $f;
            }
        }
    }
    $vcFiles = array_values(array_unique($vcFiles));
    if (!$vcFiles) {
        warn('HTML/PHP-лендингов в оффере не найдено: ' . $offerPath);
        if ($ogInteractiveMode) {
            $verifyCopy = false;
            $ogInteractiveNeedMenu = true;
            $ogInteractivePauseBeforeMenu = true;
            goto OG_INTERACTIVE_MENU;
        }
        exit(1);
    }
    
    
    
    $vcVisible = static function (string $raw): string {
        $s = preg_replace('/<\?(php|=)?[\s\S]*?\?>/i', ' ', $raw) ?? $raw;
        $s = preg_replace('/<script[\s\S]*?<\/script\s*>/i', ' ', $s) ?? $s;
        $s = preg_replace('/<script[\s\S]*$/i', ' ', $s) ?? $s; 
        $s = preg_replace('/<style[\s\S]*?<\/style\s*>/i', ' ', $s) ?? $s;
        $s = preg_replace('/<(template|noscript)[\s\S]*?<\/\1\s*>/i', ' ', $s) ?? $s;
        $s = preg_replace('/<!--[\s\S]*?-->/', ' ', $s) ?? $s;
        $s = strip_tags($s);
        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $s = function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);
        return (string)preg_replace('/[^a-z0-9\x{0400}-\x{04FF}]+/u', '', $s);
    };
    $anyLeak = false;
    $anyChecked = false;
    foreach ($vcFiles as $f) {
        $rel = str_replace($offerPath . '/', '', $f);
        $cur = (string)@file_get_contents($f);
        $enc = (bool)preg_match('/\bdata-og-enc-(html|head)\s*=/i', $cur);
        $bk = $backupDir . '/' . md5($f);
        if (!is_file($bk)) {
            warn($rel . '  →  нет бэкапа (_og_backup) — не с чем сравнить. Сначала запусти патч с флагами защиты.');
            continue;
        }
        $anyChecked = true;
        $ov = $vcVisible((string)@file_get_contents($bk));
        $cv = $vcVisible($cur);
        if (strlen($ov) < 16) {
            if ($enc) {
                ok($rel . '  →  КОПИЯ МЕРТВА: контент зашифрован (текста в оригинале почти нет — проверь картинки визуально).');
            } else {
                warn($rel . '  →  мало текста в оригинале и нет data-og-enc — проверь вручную.');
            }
            continue;
        }
        $hits = 0;
        $total = 0;
        $leakHit = '';
        $win = 24;
        $step = 20;
        for ($i = 0; $i + $win <= strlen($ov) && $total < 300; $i += $step) {
            $chunk = substr($ov, $i, $win);
            $total++;
            if (strpos($cv, $chunk) !== false) {
                $hits++;
                if ($leakHit === '') {
                    $leakHit = $chunk;
                }
            }
        }
        $ratio = $total > 0 ? $hits / $total : 0;
        
        
        $phpRuntimeProtected = str_ends_with($f, '.php')
            && preg_match('/\'1\'\s*===\s*\'1\'\s*&&\s*empty\(\$GLOBALS\[\'__og_trap_ob\'\]\)/', $cur) === 1;
        if ($phpRuntimeProtected) {
            ok($rel . '  →  .php под RUNTIME ob-шифрацией: исходник с текстом — это норма (шифрует сервер на лету, копия файла не запускает PHP → пусто).');
            warn('   ПРОВЕРЯТЬ ПО HTTP, не по файлу:  curl -s -H "Host: evil.test" https://ТВОЙ-ДОМЕН/  → НЕ должно быть текста лендинга (будет пустой/blank).');
            continue;
        }
        if ($hits > 0 && $ratio >= 0.10) {
            $anyLeak = true;
            fail($rel . '  →  КОПИЯ БУДЕТ РАБОТАТЬ: видимый текст лендинга в файле открыт ('
                . round($ratio * 100) . '% фраз оригинала совпали)'
                . ($enc ? ' — есть data-og-enc, но плейнтекст НЕ удалён!' : ' — data-og-enc нет, шифрование не применялось'));
            warn('   пример совпавшего текста: «' . $leakHit . '»');
            if (str_ends_with($f, '.php')) {
                warn('   .php без runtime-защиты — перепатчи с --og-aggressive=max (включит runtime ob-шифрацию рендера).');
            }
        } elseif ($enc) {
            ok($rel . '  →  КОПИЯ МЕРТВА: текста лендинга из оригинала в файле НЕТ, контент зашифрован.');
        } else {
            $anyLeak = true;
            fail($rel . '  →  КОПИЯ СКОРЕЕ ЖИВА: data-og-enc нет (шифрование не применялось). Совпало '
                . round($ratio * 100) . '% — проверь файл вручную.');
        }
    }
    echo "\n";
    if (!$anyChecked) {
        fail('ИТОГ: не с чем сравнивать (нет _og_backup). Запусти патч с флагами защиты, потом --verify-copy.');
        if ($ogInteractiveMode) {
            $verifyCopy = false;
            $ogInteractiveNeedMenu = true;
            $ogInteractivePauseBeforeMenu = true;
            goto OG_INTERACTIVE_MENU;
        }
        exit(1);
    }
    if ($anyLeak) {
        fail('ИТОГ: КОПИЯ БУДЕТ РАБОТАТЬ — контент лендинга в файле. Перепатчи: --canonical-host=ДОМЕН --og-encrypt-body=1 --og-encrypt-js=1 --og-aggressive=max и убедись, что патч НЕ оборвался «СТРОГИЙ РЕЖИМ».');
        if ($ogInteractiveMode) {
            $verifyCopy = false;
            $ogInteractiveNeedMenu = true;
            $ogInteractivePauseBeforeMenu = true;
            goto OG_INTERACTIVE_MENU;
        }
        exit(1);
    }
    ok('ИТОГ: текста лендинга в файле НЕТ — копия (file:// / чужой хост) мертва. Если в браузере копия всё равно показывает ленд — ты открываешь НЕ этот файл (кэш / другой путь / боевой домен).');
    if ($ogInteractiveMode) {
        $verifyCopy = false;
        $ogInteractiveNeedMenu = true;
        $ogInteractivePauseBeforeMenu = true;
        goto OG_INTERACTIVE_MENU;
    }
    exit(0);
}



function og_patch_repair_deployed_bot_protect(string $protectPath, string $webSecretPath): array
{
    $notes = [];
    if (!is_file($protectPath)) {
        return ['ok' => false, 'changed' => false, 'notes' => ['bot-protect missing']];
    }
    $src = (string)@file_get_contents($protectPath);
    $out = $src;
    $changed = false;
    if (!str_contains($out, 'function og_secret_read_bytes')) {
        $inject = <<<'OGSEC'
function og_secret_read_bytes(?string $primaryPath = null): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    $paths = [];
    if ($primaryPath !== null && $primaryPath !== '' && $primaryPath !== '__OG_SECRET_PATH__') {
        $paths[] = $primaryPath;
    }
    $paths[] = __DIR__ . '/_og_data/.og_secret';
    foreach ($paths as $p) {
        if ($p === '' || !@is_readable($p)) {
            continue;
        }
        $raw = @file_get_contents($p);
        if (is_string($raw) && strlen($raw) >= 32) {
            $cached = $raw;
            return $cached;
        }
    }
    return '';
}

OGSEC;
        $r = preg_replace('/\nfunction og_payload_key_bytes\(/', "\n" . $inject . "function og_payload_key_bytes(", $out, 1);
        if ($r !== null) {
            $out = $r;
            $changed = true;
            $notes[] = 'og_secret_read_bytes';
        }
    }
    if ($webSecretPath !== '' && str_contains($out, '__OG_SECRET_PATH__')) {
        $out = str_replace('__OG_SECRET_PATH__', $webSecretPath, $out);
        $changed = true;
        $notes[] = 'secret path in bot-protect';
    }
    if ($changed) {
        @file_put_contents($protectPath, $out, LOCK_EX);
        $lintOut = [];
        $lintRc = 0;
        @exec(escapeshellcmd(PHP_BINARY) . ' -l ' . escapeshellarg($protectPath) . ' 2>&1', $lintOut, $lintRc);
        if ($lintRc !== 0) {
            @file_put_contents($protectPath, $src, LOCK_EX);
            return ['ok' => false, 'changed' => false, 'notes' => ['php -l fail']];
        }
    }
    return ['ok' => true, 'changed' => $changed, 'notes' => $notes];
}

function og_patch_cli_site_up(string $offerPath, string $dataDir, string $protectDest, bool $dryRun): void
{
    head('SITE-UP — відновлення доступу до сайту');
    pstat('Patch root', $offerPath);
    if ($dryRun) {
        warn('[DRY] без запису');
        return;
    }
    $owner = og_patch_resolve_web_owner($offerPath);
    $fx = og_patch_fix_og_data_writable($offerPath, $owner);
    foreach ($fx['notes'] as $n) {
        ok('AUTO-FIX: ' . $n);
    }
    if (!$fx['fixed']) {
        fail('_og_data не writable — sudo php patch.php ' . $offerPath . ' --site-up');
        return;
    }
    ok('_og_data: ' . $fx['path']);
    $sec = og_patch_ensure_webroot_secret($offerPath, $owner, is_file($protectDest) ? $protectDest : null);
    if ($sec['ok']) {
        ok('Секрет: ' . $sec['path'] . ($sec['copied'] ? ' (з parent)' : ''));
    } else {
        fail('Не вдалося _og_data/.og_secret');
        return;
    }
    $ht = $offerPath . '/.htaccess';
    if (!is_file($ht) || !str_contains((string)@file_get_contents($ht), '[OfferGuard:universal-runtime]')) {
        if (function_exists('og_patch_write_universal_htaccess_rules')) {
            og_patch_write_universal_htaccess_rules($ht, false, true);
            ok('AUTO-FIX: .htaccess OfferGuard /_site/*');
        } else {
            info('Додай rewrite: php patch.php ' . $offerPath . ' --og-htaccess=1');
        }
    }
    $now = time();
    $cleared = 0;
    foreach (@glob($dataDir . '/*.json') ?: [] as $jf) {
        if (!preg_match('/^[a-f0-9]{32}\.json$/', basename($jf))) {
            continue;
        }
        $fd = json_decode((string)@file_get_contents($jf), true) ?: [];
        $atk = (int)($fd['atk_block'] ?? 0);
        $rl = (int)($fd['rl_block'] ?? 0);
        if ($atk <= $now && $rl <= $now) {
            continue;
        }
        unset($fd['atk_block'], $fd['rl_block'], $fd['ban_reason'], $fd['ban_source'],
            $fd['ban_type'], $fd['ban_until'], $fd['ban_logged_at'],
            $fd['suspect'], $fd['suspect_reasons'], $fd['timing_strikes']);
        @file_put_contents($jf, json_encode($fd, JSON_UNESCAPED_UNICODE), LOCK_EX);
        $cleared++;
    }
    if ($cleared > 0) {
        ok("Знято тимчасових банів: $cleared IP");
    }
    $sshIp = '';
    $sshConn = getenv('SSH_CONNECTION');
    if (is_string($sshConn) && trim($sshConn) !== '') {
        $parts = preg_split('/\s+/', trim($sshConn));
        if (!empty($parts[0]) && filter_var($parts[0], FILTER_VALIDATE_IP)) {
            $sshIp = $parts[0];
        }
    }
    if ($sshIp !== '') {
        og_clear_ip_ban_state_all($offerPath, $dataDir, $sshIp);
        $wlFile = $dataDir . '/whitelist.txt';
        $wlList = is_file($wlFile) ? array_values(array_filter(array_map('trim', file($wlFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []))) : [];
        if (!in_array($sshIp, $wlList, true)) {
            $wlList[] = $sshIp;
            @file_put_contents($wlFile, implode("\n", $wlList) . "\n", LOCK_EX);
        }
        ok('SSH IP розбан + whitelist: ' . $sshIp);
    }
    if (is_file($protectDest)) {
        $repair = og_patch_repair_deployed_bot_protect($protectDest, $sec['path']);
        if ($repair['changed']) {
            foreach ($repair['notes'] as $note) {
                ok('bot-protect: ' . $note);
            }
        }
    }
    out('');
    ok('Далі: php patch.php ' . $offerPath . ' --canonical-host=basincx.info');
    ok('Перевірка: php patch.php ' . $offerPath . ' --doctor');
    out('');
}


if ($siteUp) {
    og_patch_cli_site_up($offerPath, $dataDir, $protectDest, $dryRun);
    if ($ogInteractiveMode) {
        $siteUp = false;
        $ogInteractiveNeedMenu = true;
        $ogInteractivePauseBeforeMenu = true;
        goto OG_INTERACTIVE_MENU;
    }
    exit(0);
}

/**
 * Extract inline script blocks from HTML without being confused by "</script>"
 * sequences inside JS strings/template literals/comments.
 *
 * @return array<int, array{attrs:string, body:string}>
 */
function og_patch_extract_script_blocks_safe(string $html): array
{
    $res = [];
    $len = strlen($html);
    $off = 0;
    while ($off < $len) {
        $sp = stripos($html, '<script', $off);
        if ($sp === false) {
            break;
        }
        $i = $sp + 7;
        // Parse opening tag end, honoring quoted attrs.
        $q = '';
        while ($i < $len) {
            $ch = $html[$i];
            if ($q === '') {
                if ($ch === '"' || $ch === "'") {
                    $q = $ch;
                } elseif ($ch === '>') {
                    break;
                }
            } else {
                if ($ch === '\\') {
                    $i++;
                } elseif ($ch === $q) {
                    $q = '';
                }
            }
            $i++;
        }
        if ($i >= $len || $html[$i] !== '>') {
            break;
        }
        $attrs = (string)substr($html, $sp + 7, $i - ($sp + 7));
        $bodyStart = $i + 1;
        $j = $bodyStart;
        $state = 'n'; // n,l,b,',",`,r,R
        $prevSig = '';
        $closeStart = -1;
        $closeGt = -1;
        while ($j < $len) {
            $ch = $html[$j];
            $nx = $j + 1 < $len ? $html[$j + 1] : '';
            if ($state === 'n') {
                if ($ch === '<' && strncasecmp(substr($html, $j, 9), '</script>', 9) === 0) {
                    $closeStart = $j;
                    $closeGt = $j + 8;
                    break;
                }
                if ($ch === '"' || $ch === "'" || $ch === '`') {
                    $state = $ch;
                    $j++;
                    continue;
                }
                if ($ch === '/' && $nx === '/') {
                    $state = 'l';
                    $j += 2;
                    continue;
                }
                if ($ch === '/' && $nx === '*') {
                    $state = 'b';
                    $j += 2;
                    continue;
                }
                if ($ch === '/' && og_patch_js_can_start_regex($html, $j)) {
                    $state = 'r';
                    $j++;
                    continue;
                }
                if (!ctype_space($ch)) {
                    $prevSig = $ch;
                }
                $j++;
                continue;
            }
            if ($state === 'l') {
                if ($ch === "\n" || $ch === "\r") {
                    $state = 'n';
                }
                $j++;
                continue;
            }
            if ($state === 'b') {
                if ($ch === '*' && $nx === '/') {
                    $state = 'n';
                    $j += 2;
                    continue;
                }
                $j++;
                continue;
            }
            if ($state === 'r') {
                if ($ch === '\\') {
                    $j += 2;
                    continue;
                }
                if ($ch === '[') {
                    $state = 'R';
                    $j++;
                    continue;
                }
                if ($ch === '/') {
                    while ($j + 1 < $len && preg_match('/[a-z]/i', $html[$j + 1])) {
                        $j++;
                    }
                    $state = 'n';
                    $prevSig = '/';
                    $j++;
                    continue;
                }
                $j++;
                continue;
            }
            if ($state === 'R') {
                if ($ch === '\\') {
                    $j += 2;
                    continue;
                }
                if ($ch === ']') {
                    $state = 'r';
                }
                $j++;
                continue;
            }
            // Quoted string/template states (' " `)
            if ($ch === '\\') {
                $j += 2;
                continue;
            }
            if ($ch === $state) {
                $state = 'n';
            }
            $j++;
        }
        if ($closeStart < 0) {
            break;
        }
        $body = (string)substr($html, $bodyStart, $closeStart - $bodyStart);
        $res[] = ['attrs' => $attrs, 'body' => $body];
        $off = $closeGt + 1;
    }

    return $res;
}

if ($doctor) {
    head("DOCTOR — діагностика OfferGuard");
    pstat('Patch root', $offerPath);
    $docFail = 0; $docWarn = 0;
    $owner = og_patch_resolve_web_owner($offerPath);

    if (!$dryRun) {
        $ogDocFx = og_patch_fix_og_data_writable($offerPath, $owner);
        foreach ($ogDocFx['notes'] as $dn) {
            ok('AUTO-FIX: ' . $dn);
        }
        $ogDocBoot = og_patch_ensure_og_data_dir($offerPath, $owner);
        if ($ogDocBoot['created']) {
            ok('AUTO-FIX: _og_data → ' . $ogDocBoot['path']);
        }
        $ogDocSec = og_patch_ensure_webroot_secret($offerPath, $owner, is_file($protectDest) ? $protectDest : null);
        if ($ogDocSec['ok']) {
            ok('AUTO-FIX: .og_secret → ' . $ogDocSec['path']);
        }
        if ($owner !== null && function_exists('posix_geteuid') && posix_geteuid() === 0) {
            foreach ([$protectDest, $dataDir] as $ogDocCh) {
                if ($ogDocCh !== '' && (is_file($ogDocCh) || is_dir($ogDocCh))) {
                    og_patch_chown_recursive($ogDocCh, $owner[0], $owner[1]);
                }
            }
        }
    }

    $phpV = PHP_VERSION;
    pstat('PHP', $phpV);
    if (version_compare($phpV, '7.4', '<')) {
        warn('PHP < 7.4 — bot-protect може падати з фаталом.');
        $docFail++;
    }
    foreach (['openssl', 'mbstring', 'hash', 'json'] as $mod) {
        if (!extension_loaded($mod)) {
            warn("PHP-модуль '$mod' відсутній — bot-protect може падати.");
            $docFail++;
        }
    }

    if (!is_file($protectDest)) {
        warn('bot-protect.php відсутній → захист не активна.');
        $docFail++;
    } else {
        ok('bot-protect.php присутній (' . round(filesize($protectDest) / 1024, 1) . ' KB)');
        $lintOut = []; $lintRc = 0;
        @exec(escapeshellcmd(PHP_BINARY) . ' -l ' . escapeshellarg($protectDest) . ' 2>&1', $lintOut, $lintRc);
        if ($lintRc !== 0) {
            warn('php -l bot-protect.php FAIL: ' . trim(implode(' ', $lintOut)));
            $docFail++;
        } else {
            ok('bot-protect.php — синтаксис OK');
        }
        $st = @stat($protectDest);
        if ($st && ($st['mode'] & 0044) === 0) {
            warn('bot-protect.php права 0' . sprintf('%o', $st['mode'] & 0777) . ' — Apache може не прочитати.');
            $docFail++;
        }
    }

    if (!is_dir($dataDir)) {
        warn('_og_data відсутня → bot-protect не зможе писати логи.');
        $docFail++;
    } else {
        $testFile = $dataDir . '/.og_doctor_write_test';
        if (@file_put_contents($testFile, 'x') === false) {
            warn('_og_data НЕ writable. Якщо Apache — інший uid, він не зможе писати → fatal.');
            $docWarn++;
        } else {
            @unlink($testFile);
            ok('_og_data writable ✓');
        }
    }

    $secretFound = '';
    foreach ([dirname($offerPath) . '/.og_secret', $offerPath . '/_og_data/.og_secret'] as $sp) {
        if (is_file($sp)) { $secretFound = $sp; break; }
    }
    if ($secretFound === '') {
        warn('.og_secret НЕ знайдено → шифрування зламано.');
        $docFail++;
    } else {
        ok('.og_secret OK (' . filesize($secretFound) . ' байт): ' . $secretFound);
        $stSec = @stat($secretFound);
        if ($stSec && ($stSec['mode'] & 0044) === 0) {
            warn('.og_secret права 0' . sprintf('%o', $stSec['mode'] & 0777) . ' — Apache не прочитає → blank page.');
            warn('Виправлення: chmod 0644 ' . $secretFound);
            $docFail++;
        }
    }

    $ht = $offerPath . '/.htaccess';
    if (is_file($ht)) {
        $htSrc = (string)@file_get_contents($ht);
        if (str_contains($htSrc, '[OfferGuard:universal-runtime]')) {
            ok('.htaccess: OfferGuard блок присутній');
        } else {
            warn('.htaccess без OfferGuard блоку — /_site/* буде 404.');
            $docWarn++;
        }
    } else {
        warn('.htaccess відсутній → JS fallback на /bot-protect.php?_og_ep=… (працює, але повільніше).');
        $docWarn++;
    }

    $patchedCount = 0; $brokenJs = 0; $totalScripts = 0; $brokenDetails = [];
    try {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($offerPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        $it->setMaxDepth(3);
        foreach ($it as $f) {
            if (!$f->isFile()) continue;
            $p = $f->getPathname();
            if (og_patch_should_skip_walk_path($p)) continue;
            $ext = strtolower($f->getExtension());
            if (!in_array($ext, ['html', 'htm', 'php'], true)) continue;
            $full = (string)@file_get_contents($p);
            if (!str_contains($full, '[OfferGuard:')) continue;
            $patchedCount++;
            $scripts = og_patch_extract_script_blocks_safe($full);
            if ($scripts !== []) {
                foreach ($scripts as $idx => $sm) {
                    $attrs = strtolower((string)($sm['attrs'] ?? ''));
                    $bd = (string)($sm['body'] ?? '');
                    if (trim($bd) === '') continue;
                    // Не считаем JSON/шаблонные non-js script-блоки.
                    if (preg_match('/\btype\s*=\s*["\'](?:application\/ld\+json|application\/json|text\/template|text\/x-template|text\/plain)["\']/i', $attrs)) {
                        continue;
                    }
                    // Для doctor проверяем только исполняемый guard/OG JS, иначе много false-positive
                    // на стороннем партнёрском куске (placeholder-шаблоны, трекеры и т.п.).
                    $isOgScript = str_contains($bd, 'OfferGuard')
                        || str_contains($bd, '__og')
                        || str_contains($bd, '_og')
                        || str_contains($bd, 'bot-protect.php?_og_ep=');
                    if (!$isOgScript) {
                        continue;
                    }
                    $totalScripts++;
                    $qOk = og_patch_js_quotes_balanced($bd);
                    $dOk = og_patch_js_delims_balanced($bd);
                    if (!$qOk || !$dOk) {
                        $brokenJs++;
                        if (count($brokenDetails) < 8) {
                            $brokenDetails[] = str_replace($offerPath . '/', '', $p)
                                . '::script#' . ($idx + 1)
                                . ' [quotes=' . ($qOk ? 'ok' : 'fail')
                                . ', delims=' . ($dOk ? 'ok' : 'fail') . ']';
                        }
                    }
                }
            }
        }
    } catch (Throwable $e) {}
    if ($patchedCount === 0) {
        warn('Жоден HTML/PHP не патчено → захист не вшита у відповіді сервера.');
        $docFail++;
    } else {
        ok("HTML/PHP з захистом: $patchedCount файл(ів), $totalScripts guard-скриптів");
        if ($brokenJs > 0) {
            warn("$brokenJs/$totalScripts guard-скриптів з дисбалансом — JS guard може ламатись.");
            foreach ($brokenDetails as $bd) {
                warn('  • ' . $bd);
            }
            $docFail++;
        }
    }

    if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
        $owner = og_patch_detect_web_owner($offerPath);
        if ($owner !== null && is_file($protectDest)) {
            $bpSt = @stat($protectDest);
            if ($bpSt && $bpSt['uid'] !== $owner[0]) {
                warn('КРИТИЧНО: bot-protect.php uid=' . $bpSt['uid']
                    . ' ≠ uid сайту ' . $owner[0]
                    . ' → Apache (' . $owner[0] . ') НЕ зможе писати в _og_data → bot-protect фатал → blank page.');
                // Автоматично виправляємо прямо тут.
                info('Виправляю права автоматично: chown -R ' . $owner[0] . ':' . $owner[1] . ' OfferGuard-файлів…');
                og_patch_chown_recursive($protectDest, $owner[0], $owner[1]);
                @chmod($protectDest, 0644);
                if (is_dir($dataDir)) {
                    og_patch_chown_recursive($dataDir, $owner[0], $owner[1]);
                    @chmod($dataDir, 0755);
                }
                if ($secretFound !== '') {
                    og_patch_chown_recursive($secretFound, $owner[0], $owner[1]);
                    @chmod($secretFound, 0644); // Apache повинен читати
                }
                if (is_dir($backupDir)) og_patch_chown_recursive($backupDir, $owner[0], $owner[1]);
                $htAccess = $offerPath . '/.htaccess';
                if (is_file($htAccess)) {
                    og_patch_chown_recursive($htAccess, $owner[0], $owner[1]);
                    @chmod($htAccess, 0644);
                }
                $robots = $offerPath . '/robots.txt';
                if (is_file($robots)) og_patch_chown_recursive($robots, $owner[0], $owner[1]);
                // Cloak-папки теж
                foreach (@glob($offerPath . '/*', GLOB_ONLYDIR) ?: [] as $dp) {
                    if (is_file($dp . '/.og_cloak')) {
                        og_patch_chown_recursive($dp, $owner[0], $owner[1]);
                    }
                }
                $bpStAfter = @stat($protectDest);
                if ($bpStAfter && $bpStAfter['uid'] === $owner[0]) {
                    ok('Права виправлено → uid=' . $owner[0] . ' gid=' . $owner[1] . ' (Apache тепер зможе писати).');
                } else {
                    warn('chown не вдався. Виконай вручну: chown -R ' . $owner[0] . ':' . $owner[1] . ' '
                        . $protectDest . ' ' . $dataDir . ' ' . $secretFound);
                    $docFail++;
                }
            } else {
                ok('Власник bot-protect.php = власник сайту (uid=' . $owner[0] . ') ✓');
            }
        }
    }

    // 9) Активні бани (БЫЛИ ли вообще запросы?)
    if (is_dir($dataDir)) {
        $banJsons = [];
        foreach (@glob($dataDir . '/*.json') ?: [] as $jf) {
            if (preg_match('/^[a-f0-9]{32}\.json$/', basename($jf))) {
                $banJsons[] = $jf;
            }
        }
        if ($banJsons === []) {
            warn('У _og_data немає жодного <ip-md5>.json — bot-protect.php ніколи не приймав запитів.');
            warn('Це означає: Apache НЕ викликає bot-protect, або сайт не вантажиться → JS-гард не стукається.');
            warn('Перевір curl-ом: curl -sI https://<host>/bot-protect.php?_og_ep=v');
            $docFail++;
        } else {
            ok('У _og_data знайдено ' . count($banJsons) . ' ip-state файлів — bot-protect.php приймав запити.');
            $now = time();
            $active = 0;
            $autoUnbanned = 0;
            foreach ($banJsons as $jf) {
                $fd = json_decode((string)@file_get_contents($jf), true) ?: [];
                $atk = (int)($fd['atk_block'] ?? 0);
                $rl  = (int)($fd['rl_block']  ?? 0);
                if ($atk > $now || $rl > $now) {
                    $active++;
                    $reason = (string)($fd['ban_reason'] ?? '?');
                    $src = (string)($fd['ban_source'] ?? '?');
                    $bannedIp = (string)($fd['ip'] ?? '?');
                    info("  bann IP=$bannedIp reason=$reason source=$src");
                    // AUTO-UNBAN: timing_uniform та suspect_score часто false-positive
                    // на наш JS-heartbeat. Doctor зніма ці бани автоматично.
                    $falsePositive = str_contains($reason, 'timing_uniform')
                        || str_contains($reason, 'suspect_score')
                        || str_contains($reason, 'rate_limit')
                        || str_contains($src, 'timing_analysis')
                        || str_contains($src, 'suspect_score')
                        || str_contains($src, 'rate_limit')
                        || str_contains($src, 'header_fingerprint');
                    if ($falsePositive) {
                        unset($fd['atk_block'], $fd['rl_block'], $fd['ban_reason'], $fd['ban_source'],
                              $fd['ban_type'], $fd['ban_until'], $fd['ban_logged_at'],
                              $fd['suspect'], $fd['suspect_reasons'], $fd['timing_strikes']);
                        @file_put_contents($jf, json_encode($fd, JSON_UNESCAPED_UNICODE), LOCK_EX);
                        ok("  → AUTO-UNBAN: $bannedIp (false positive: $reason)");
                        $autoUnbanned++;
                    }
                }
            }
            if ($autoUnbanned > 0) {
                ok("Знято $autoUnbanned помилкових банів автоматично.");
            }
            $remaining = $active - $autoUnbanned;
            if ($remaining > 0) {
                warn("Активних банів залишилось: $remaining. Якщо це твій IP — `php patch.php $offerPath --unban <твій IP>`");
                $docWarn++;
            }
        }
    }

    // 10) Логи runtime (есть ли свежие записи?)
    $rtLogPath = $dataDir . '/og_runtime.log';
    if (is_file($rtLogPath)) {
        $sz = filesize($rtLogPath);
        ok('og_runtime.log: ' . round($sz / 1024, 1) . ' KB');
        if ($sz > 0) {
            $tail = @file_get_contents($rtLogPath, false, null, max(0, $sz - 1024), 1024);
            if (is_string($tail) && trim($tail) !== '') {
                $lines = array_slice(array_filter(explode("\n", $tail)), -3);
                foreach ($lines as $ln) {
                    info('  ' . substr(trim($ln), 0, 140));
                }
            }
        }
    } else {
        warn('og_runtime.log відсутній → bot-protect не писав жодного запису.');
        $docWarn++;
    }

    // 11a) HTTP-тест: спробуй реально вдарити в bot-protect через curl/file_get_contents.
    // Це покаже ПОЧЕМУ він 403'ить.
    $hostJsCanon = '';
    if (is_file($protectDest)) {
        // Читаємо весь файл (regex по 64KB може промахнутись якщо config далі)
        $bpSrc = (string)@file_get_contents($protectDest);
        if (preg_match('/[\'"]canonical_host[\'"]\s*=>\s*[\'"]([^\'"]+)[\'"]/', $bpSrc, $cm)) {
            $hostJsCanon = $cm[1];
        }
        // Fallback: --canonical-host=… з CLI
        if ($hostJsCanon === '' || $hostJsCanon === 'OG_CANONICAL_HOST_CHANGE_ME') {
            foreach ($args as $a) {
                if (str_starts_with((string)$a, '--canonical-host=')) {
                    $hostJsCanon = substr((string)$a, strlen('--canonical-host='));
                    break;
                }
            }
        }
        // Fallback: вгадати з шляху (basincx.info/public_html → basincx.info)
        if ($hostJsCanon === '') {
            $base = basename(dirname($offerPath));
            if (preg_match('/^[a-z0-9][a-z0-9.\-]+\.[a-z]{2,}$/i', $base)) {
                $hostJsCanon = strtolower($base);
            }
        }
    }
    if ($hostJsCanon === '' || $hostJsCanon === 'OG_CANONICAL_HOST_CHANGE_ME') {
        info('HTTP-тест пропущено: canonical_host не визначено в bot-protect (re-patch виправить).');
    } elseif (!function_exists('curl_init')) {
        info('HTTP-тест пропущено: php-curl не встановлено. apt-get install php-curl');
    } elseif (true) {
        $testUrl = 'https://' . $hostJsCanon . '/bot-protect.php?_og_ep=v';
        $ch = curl_init($testUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER => [
                'Origin: https://' . $hostJsCanon,
                'Referer: https://' . $hostJsCanon . '/',
                'Sec-Fetch-Site: same-origin',
                'X-Requested-With: XMLHttpRequest',
            ],
        ]);
        $resp = @curl_exec($ch);
        $code = (int)@curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = @curl_error($ch);
        curl_close($ch);
        if ($code === 0) {
            warn('HTTP-тест: curl exec failed: ' . $err);
            $docWarn++;
        } elseif ($code === 200) {
            ok("HTTP-тест /bot-protect.php?_og_ep=v: $code — bot-protect відповідає нормально ✓");
        } elseif ($code === 403) {
            warn("HTTP-тест /bot-protect.php?_og_ep=v: 403 — bot-protect ВІДКИДАЄ запити з origin=$hostJsCanon");
            // Витягуємо body для аналізу
            $body = is_string($resp) && strlen($resp) > 0
                ? substr($resp, strpos($resp, "\r\n\r\n") !== false ? strpos($resp, "\r\n\r\n") + 4 : 0)
                : '';
            $reason = '';
            if ($body !== '') {
                $j = json_decode($body, true);
                if (is_array($j)) {
                    $reason = (string)($j['r'] ?? $j['reason'] ?? '');
                    if ($reason !== '') info("  reason: $reason");
                    if (!empty($j['blocked'])) info('  blocked: ' . (is_string($j['blocked']) ? $j['blocked'] : 'true'));
                } else {
                    info('  body: ' . substr($body, 0, 150));
                }
            }
            // Точечні підказки за reason'ом:
            if ($reason === 'no_secret') {
                warn('  ПРИЧИНА: bot-protect не може ПРОЧИТАТИ .og_secret (права).');
                if ($secretFound !== '') {
                    $st = @stat($secretFound);
                    $perms = $st ? sprintf('%o', $st['mode'] & 0777) : '?';
                    $own = $st ? $st['uid'] : '?';
                    warn('  Зараз: uid=' . $own . ' права=0' . $perms . ' (' . $secretFound . ')');
                    warn('  ВИПРАВ: chmod 0644 ' . $secretFound . ' && chown ' . ($owner[0] ?? '?') . ':' . ($owner[1] ?? '?') . ' ' . $secretFound);
                    if ($owner !== null) {
                        @chmod($secretFound, 0644);
                        @chown($secretFound, $owner[0]);
                        @chgrp($secretFound, $owner[1]);
                        info('  Спробував виправити автоматично. Перевір ще раз doctor.');
                    }
                }
            } elseif ($reason === '' && $code === 403) {
                info('  Можливі причини: live-pub token устарел (re-patch виправить), IP в бан-листі, Origin/Referer mismatch.');
            }
            $docFail++;
        } else {
            warn("HTTP-тест /bot-protect.php?_og_ep=v: $code (очікувано 200/403). " . substr($resp ?: '', 0, 100));
            $docWarn++;
        }
    }

    // 11) Спробуємо запустити bot-protect.php напряму (як CLI) — чи не падає
    if (is_file($protectDest)) {
        $testInput = json_encode(['p' => base64_encode(json_encode([
            'a' => 'issue', 'h' => 'localhost', 'o' => 'http://localhost', 'u' => '/',
            't' => time() * 1000, 'r' => 'doctor', 'fp' => 'doctor', 'tid' => 'doctor',
        ]))]);
        $env = [
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/_site/v?doctor=1',
            'HTTP_HOST' => 'localhost',
            'REMOTE_ADDR' => '127.0.0.1',
        ];
        $envStr = 'env ';
        foreach ($env as $k => $v) {
            $envStr .= escapeshellarg($k . '=' . $v) . ' ';
        }
        $cmd = $envStr . escapeshellcmd(PHP_BINARY) . ' ' . escapeshellarg($protectDest) . ' 2>&1';
        $rtOut = []; $rtRc = 0;
        @exec($cmd, $rtOut, $rtRc);
        $rtAll = implode("\n", $rtOut);
        if (str_contains($rtAll, 'Fatal error') || str_contains($rtAll, 'Parse error')) {
            warn('bot-protect.php при запуске даёт FATAL/PARSE:');
            foreach (array_slice($rtOut, 0, 5) as $ol) info('  ' . substr($ol, 0, 200));
            $docFail++;
        } elseif ($rtRc !== 0 && $rtAll !== '') {
            info('bot-protect.php CLI exit=' . $rtRc . ', output: ' . substr($rtAll, 0, 200));
        } else {
            ok('bot-protect.php запускається без fatal-помилок');
        }
    }

    // ─── Asset integrity check ────────────────────────────────────
    // Walks _og_assets/ and verifies every .enc is valid JSON {c,iv}.
    // Also reports basename collisions and gives top-N largest files.
    out('');
    pstat('Asset audit', $ogAssetsSubdir . '/');
    $docAssetsDir = $offerPath . '/' . trim($ogAssetsSubdir, '/');
    if (!is_dir($docAssetsDir)) {
        warn($ogAssetsSubdir . '/ не існує — захист ресурсів не зашифрована (запусти patch без --rollback)');
        $docWarn++;
    } else {
        $docAssetFiles = @glob($docAssetsDir . '/*.enc') ?: [];
        $docAssetTotal = count($docAssetFiles);
        $docAssetBad   = [];
        $docAssetSizes = [];
        foreach ($docAssetFiles as $docAef) {
            $docAesz = @filesize($docAef);
            if ($docAesz === false || $docAesz < 30) {
                $docAssetBad[] = basename($docAef) . '(' . ($docAesz === false ? 'unreadable' : 'too_small:' . $docAesz) . ')';
                continue;
            }
            // Quick structural check without loading the full payload:
            // valid files start with `{"c":"` and end with `"}`. Read 256B head
            // + 64B tail to verify shape; full json_decode would balloon memory
            // for offer trees with thousands of large .enc bundles.
            $docHead = @file_get_contents($docAef, false, null, 0, 256);
            if ($docHead === false || $docHead === '') {
                $docAssetBad[] = basename($docAef) . '(empty)';
                continue;
            }
            if (!preg_match('/^\s*\{\s*"(?:c|iv)"\s*:/', $docHead)) {
                $docAssetBad[] = basename($docAef) . '(bad_head)';
                continue;
            }
            // For small files, do a full json_decode; for big ones, just check
            // that both "c": and "iv": occur in the head OR a small tail.
            if ($docAesz <= 16384) {
                $docFull = @file_get_contents($docAef);
                $docAj   = $docFull !== false ? json_decode($docFull, true) : null;
                if (!is_array($docAj) || !isset($docAj['c'], $docAj['iv'])) {
                    $docAssetBad[] = basename($docAef) . '(bad_json)';
                    continue;
                }
            } else {
                $docTail = @file_get_contents($docAef, false, null, max(0, $docAesz - 256));
                $docHas  = (strpos($docHead, '"c"') !== false || strpos($docTail, '"c"') !== false)
                       && (strpos($docHead, '"iv"') !== false || strpos($docTail, '"iv"') !== false)
                       && (substr(rtrim((string)$docTail), -1) === '}');
                if (!$docHas) {
                    $docAssetBad[] = basename($docAef) . '(bad_shape:' . $docAesz . 'b)';
                    continue;
                }
            }
            $docAssetSizes[basename($docAef)] = $docAesz;
        }
        if ($docAssetTotal === 0) {
            warn($ogAssetsSubdir . '/ порожня — або захист не активна, або encryption phase впала.');
            $docWarn++;
        } else {
            ok('Знайдено ' . $docAssetTotal . ' .enc файлів у ' . $ogAssetsSubdir . '/');
        }
        if (!empty($docAssetBad)) {
            warn('Битих .enc: ' . count($docAssetBad) . ' (приклади: '
                . implode(', ', array_slice($docAssetBad, 0, 5))
                . (count($docAssetBad) > 5 ? ' …+' . (count($docAssetBad) - 5) : '') . ')');
            $docFail++;
        }
        // Sample test: try to fetch /_site/a for one valid file via CLI HTTP loopback?
        // Skipped — origin/token chain not available in CLI context.
        // Instead: ensure the dir is readable by Apache.
        $docAdSt = @stat($docAssetsDir);
        if ($docAdSt && ($docAdSt['mode'] & 0044) === 0) {
            warn($ogAssetsSubdir . '/ права 0' . sprintf('%o', $docAdSt['mode'] & 0777) . ' — Apache не зможе прочитати → /_site/a 404');
            $docFail++;
        }
    }

    // ─── Recent /_site/a 404s (last 200 xlog lines) ────────────────
    // Surfaces what file IDs are failing right now in production.
    $docXlogFile = $dataDir . '/og_xlog.log';
    if (is_file($docXlogFile) && @is_readable($docXlogFile)) {
        $docXlogTail = @file($docXlogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (is_array($docXlogTail) && count($docXlogTail) > 0) {
            $docXlogTail = array_slice($docXlogTail, -500);
            $docMissHashes = [];
            foreach ($docXlogTail as $docXl) {
                // Match xlog entries about asset_get reject:not_found
                if (strpos($docXl, 'asset_get') !== false
                    && (strpos($docXl, 'reject:not_found') !== false || strpos($docXl, 'read_error') !== false)) {
                    if (preg_match('/"file"\s*:\s*"([a-f0-9]{8,64}\.enc)"/', $docXl, $mm)) {
                        $docMissHashes[$mm[1]] = ($docMissHashes[$mm[1]] ?? 0) + 1;
                    }
                }
            }
            if (!empty($docMissHashes)) {
                arsort($docMissHashes);
                $docMissTop = [];
                foreach (array_slice($docMissHashes, 0, 5, true) as $docMh => $docMc) {
                    $docMissTop[] = $docMh . '(×' . $docMc . ')';
                }
                warn('У xlog зафіксовано ' . array_sum($docMissHashes) . ' /_site/a 404 — топ: ' . implode(', ', $docMissTop));
                $docFail++;
            }
        }
    }

    out('');
    if ($docFail === 0 && $docWarn === 0) {
        ok('✓ ВСЕ OK. Захист повинна працювати.');
    } else {
        if ($docFail > 0) warn("$docFail критичних проблем — оффер може не вантажитись.");
        if ($docWarn > 0) info("$docWarn попереджень.");
        if ($docFail > 0) {
            info('Спробуй: sudo php patch.php ' . $offerPath . ' --site-up');
            info('Потім: php patch.php ' . $offerPath . ' --canonical-host=basincx.info --og-htaccess=1');
        } elseif ($docWarn > 0) {
            info('Попередження не блокують deploy — відкрий сайт у браузері і знову --doctor');
        }
        if (is_dir($dataDir)) {
            $docBans = 0;
            foreach (@glob($dataDir . '/*.json') ?: [] as $djf) {
                if (!preg_match('/^[a-f0-9]{32}\.json$/', basename($djf))) continue;
                $dfd = json_decode((string)@file_get_contents($djf), true) ?: [];
                $dn = time();
                if ((int)($dfd['atk_block'] ?? 0) > $dn || (int)($dfd['rl_block'] ?? 0) > $dn) $docBans++;
            }
            if ($docBans > 0) {
                warn("Активних банів: $docBans → php patch.php $offerPath --bans");
                info('Розбан: php patch.php ' . $offerPath . ' --unban YOUR_IP');
                info('Причина: php patch.php ' . $offerPath . ' --why YOUR_IP');
            }
        }
    }
    exit($docFail > 0 ? 1 : 0);
}

if ($showStatus) {
    head("STATUS — OfferGuard");
    pstat('Patch root', $offerPath);
    if (!empty($ogPatchResolveMeta['input']) && ($ogPatchResolveMeta['input'] ?? '') !== $offerPath) {
        pstat('CLI path', (string)$ogPatchResolveMeta['input']);
    }
    $ogStDetect = [];
    $ogStApplied = $dataDir . '/og_framework_applied.json';
    if (is_file($ogStApplied)) {
        $ogStDetect = json_decode((string)@file_get_contents($ogStApplied), true)['detect'] ?? [];
    }
    if ($ogStDetect === []) {
        [$ogStHtml, $ogStPhp, $ogStMeta] = og_patch_collect_files($offerPath, $ogAssetsSubdir);
        $ogStDetect = OgFramework::detect($offerPath, $ogStHtml, $ogStPhp, $ogStMeta, $ogPatchResolveMeta);
    }
    if (!empty($ogStDetect['offer_type'])) {
        pstat('Stack', (string)$ogStDetect['offer_type']);
    }
    if (!empty($ogStDetect['stack_adapters']) && is_array($ogStDetect['stack_adapters'])) {
        pstat('Adapters', implode(', ', $ogStDetect['stack_adapters']));
    }
    if (!empty($ogStDetect['recommended_patch_dir']) && (string)$ogStDetect['recommended_patch_dir'] !== $offerPath) {
        pstat('Alt patch dir', (string)$ogStDetect['recommended_patch_dir']);
    }
    // Ищем bot-protect.php / _og_data везде где могли остаться от прошлых патчей:
    // в текущем root, в parent, в subdirs до 2 уровней. Это покрывает случай,
    // когда раньше патчили parent dir, а сейчас — public_html (или наоборот).
    $ogStInstalls = og_patch_status_collect_installs($offerPath);
    $ogStBpFound = false;
    foreach ($ogStInstalls as $inst) {
        if (!empty($inst['bot_protect'])) { $ogStBpFound = true; break; }
    }
    pstat("bot-protect.php:", $ogStBpFound
        ? "✓ есть"
            . (count($ogStInstalls) > 1 ? " (в " . count(array_filter($ogStInstalls, fn($i) => !empty($i['bot_protect']))) . " локаціях!)" : '')
        : "✗ нет");

    pstat("Бэкап:", is_dir($backupDir) ? "✓ " . count(glob($backupDir . '/*.meta')) . " файл(ов)" : "✗ нет");
    $ogStTraces = og_patch_find_offer_guard_traces($offerPath);
    $ogStTraceDirs = $ogStTraces['dirs'];
    unset($ogStTraceDirs['_og_backup']);
    $ogStTraceN = count($ogStTraces['files']) + count($ogStTraceDirs);
    if ($ogStTraceN > 0) {
        if ($ogStBpFound) {
            pstat("OfferGuard:", "✓ установлен ({$ogStTraceN} компонентов)");
        } else {
            pstat("OfferGuard traces:", (string)$ogStTraceN . " → --cleanup (защита не активна)");
        }
    } else {
        pstat("OfferGuard:", "не установлен");
    }

    // Сводим все найденные логи в одну выборку.
    $logBlocked = null; $logRuntime = null;
    foreach ($ogStInstalls as $inst) {
        if ($logBlocked === null && !empty($inst['log_blocked'])) $logBlocked = $inst['log_blocked'];
        if ($logRuntime === null && !empty($inst['log_runtime'])) $logRuntime = $inst['log_runtime'];
    }
    $logTmp = sys_get_temp_dir() . '/og2/og_blocked.log';
    if ($logBlocked === null && is_file($logTmp)) $logBlocked = $logTmp;
    pstat("Лог:", $logBlocked
        ? $logBlocked . " (" . round(filesize($logBlocked) / 1024, 1) . " KB)"
        : "не найден");
    pstat("Ban reason log:", $logRuntime
        ? $logRuntime . " (" . round(filesize($logRuntime) / 1024, 1) . " KB)"
        : "не найден");
    // Если bot-protect живёт не на том пути — отдельная подсказка.
    if (count($ogStInstalls) > 1) {
        foreach ($ogStInstalls as $inst) {
            if (!empty($inst['bot_protect']) && rtrim((string)$inst['root'], '/') !== $offerPath) {
                pstat('⚠ ALT install', (string)$inst['root']);
            }
        }
    }
    // Используем найденный путь для последующего чтения банов/сессий ниже.
    if ($logRuntime !== null) {
        $rtLog = $logRuntime;
        $dataDir = dirname($logRuntime);
    } else {
        $rtLog = $dataDir . '/og_runtime.log';
    }
    $log = $logBlocked ?? ($logTmp);

    if (file_exists($protectDest)) {
        $bpSrc = (string)@file_get_contents($protectDest);
        if (preg_match("/'live_key_split'\\s*=>\\s*true/", $bpSrc)) {
            $kfTtl = preg_match("/'live_kfrag_ttl'\\s*=>\\s*(\\d+)/", $bpSrc, $mk) ? (int)$mk[1] : 8;
            pstat("Key-split:", "✓ ON (kfrag TTL {$kfTtl}s, hook=origin bot-protect)");
        } else {
            pstat("Key-split:", "✗ OFF (статика расшифровывается без live-hook)");
        }
        if (preg_match("/'live_payload_key'\\s*=>\\s*'OG_PAYLOAD_KEY_CHANGE_ME'/", $bpSrc)) {
            pstat("live_payload_key:", "⚠ НЕ задан (плейсхолдер) — split/encrypt не работают");
        }
    }

    
    $banCount = 0;
    if (is_dir($dataDir)) {
        $now = time();
        foreach (glob($dataDir . '/*.json') ?: [] as $f) {
            if (!preg_match('/^[a-f0-9]{32}\.json$/', basename($f))) continue;
            $fd = json_decode(@file_get_contents($f), true) ?? [];
            if ((!empty($fd['atk_block']) && $fd['atk_block'] > $now) ||
                (!empty($fd['rl_block'])  && $fd['rl_block']  > $now)) {
                $banCount++;
            }
        }
    }
    pstat("Активных банов:", (string)$banCount);

    $permFile = $dataDir . '/perm_ban.txt';
    $permCount = is_file($permFile) ? count(file($permFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []) : 0;
    pstat("Перманентный бан-лист:", $permCount . " IP");

    $wlFile = $dataDir . '/whitelist.txt';
    $wlCount = is_file($wlFile) ? count(file($wlFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []) : 0;
    pstat("Вайтлист:", $wlCount . " IP");

    if (is_file($log)) {
        $lines = file($log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $total = count($lines);
        pstat("Всего блокировок:", (string)$total);
        $reasons = [];
        foreach ($lines as $l) {
            $p = explode('|', $l);
            $r = $p[2] ?? 'unknown';
            $reasons[$r] = ($reasons[$r] ?? 0) + 1;
        }
        arsort($reasons);
        out(CYAN . "\n  Топ причин блокировки:");
        foreach (array_slice($reasons, 0, 8, true) as $r => $c) {
            out(GRAY . "    " . str_pad($r, 28) . YEL . $c);
        }
        $ips = [];
        foreach ($lines as $l) {
            $p = explode('|', $l);
            $ip = $p[1] ?? '';
            if ($ip) $ips[$ip] = ($ips[$ip] ?? 0) + 1;
        }
        arsort($ips);
        out(CYAN . "\n  Топ IP-адресов:");
        foreach (array_slice($ips, 0, 8, true) as $ip => $c) {
            out(GRAY . "    " . str_pad($ip, 20) . YEL . $c . " блок.");
        }
    }
    if (is_file($rtLog)) {
        $rtLines = file($rtLog, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $lastBan = null;
        for ($i = count($rtLines) - 1; $i >= 0; $i--) {
            if (strpos($rtLines[$i], '|BAN|type=') !== false) {
                $lastBan = $rtLines[$i];
                break;
            }
        }
        if ($lastBan) {
            $parts = explode('|', $lastBan);
            $ts = $parts[0] ?? '-';
            $lip = $parts[1] ?? '-';
            $luri = $parts[2] ?? '-';
            $meta = implode('|', array_slice($parts, 4));
            pstat("Последняя ban-причина:", $ts . " " . $lip . " " . $meta . " uri=" . $luri);
        }
    }

    if ($filterIp) {
        out("");
        head("STATUS — IP: $filterIp");
        if (!filter_var($filterIp, FILTER_VALIDATE_IP)) {
            fail("Некорректный IP-адрес: $filterIp");
            out("");
            exit(1);
        }
        $now = time();
        $dirs = og_find_og_data_dirs($offerPath, $dataDir);
        if (!$dirs) {
            warn("Не найдено ни одной папки _og_data внутри оффера");
            out("");
            exit(0);
        }
        foreach ($dirs as $dir) {
            $ipf = $dir . '/' . md5($filterIp) . '.json';
            $state = is_file($ipf) ? (json_decode((string)@file_get_contents($ipf), true) ?: []) : [];
            $atk = (!empty($state['atk_block']) && (int)$state['atk_block'] > $now) ? (int)$state['atk_block'] : 0;
            $rl  = (!empty($state['rl_block'])  && (int)$state['rl_block']  > $now) ? (int)$state['rl_block']  : 0;
            $permFile = $dir . '/perm_ban.txt';
            $perm = is_file($permFile) ? in_array($filterIp, array_map('trim', file($permFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []), true) : false;
            $reason = (string)($state['ban_reason'] ?? '-');
            $rt = $dir . '/og_runtime.log';
            $last = og_last_ban_reason_for_ip($rt, $filterIp);

            out(GRAY . "  " . B . $dir . R);
            pstat("perm_ban:", $perm ? (RED . "ДА" . GRAY) : (GREEN . "нет" . GRAY));
            pstat("atk_block:", $atk ? (RED . date('Y-m-d H:i:s', $atk) . " (" . og_remaining($atk) . ")" . GRAY) : (GREEN . "нет" . GRAY));
            pstat("rl_block:",  $rl  ? (YEL . date('Y-m-d H:i:s', $rl)  . " (" . og_remaining($rl)  . ")" . GRAY) : (GREEN . "нет" . GRAY));
            pstat("state reason:", $reason !== '' ? $reason : '-');
            if ($last) {
                pstat("last BAN:", $last);
            }
            out("");
        }
    }
    out("");
    if ($ogInteractiveMode) {
        $showStatus = false;
        $ogInteractiveNeedMenu = true;
        $ogInteractivePauseBeforeMenu = true;
        goto OG_INTERACTIVE_MENU;
    }
    exit(0);
}




if ($rollback) {
    og_patch_rollback($offerPath, $backupDir, $dryRun, false);
    if ($ogInteractiveMode) {
        $rollback = false;
        $ogInteractiveNeedMenu = true;
        $ogInteractivePauseBeforeMenu = true;
        goto OG_INTERACTIVE_MENU;
    }
    exit(0);
}




if ($cleanup) {
    og_patch_cleanup($offerPath, $backupDir, $dryRun);
    if ($ogInteractiveMode) {
        $cleanup = false;
        $ogInteractiveNeedMenu = true;
        $ogInteractivePauseBeforeMenu = true;
        goto OG_INTERACTIVE_MENU;
    }
    exit(0);
}




if ($showBans) {
    og_patch_cli_show_bans($offerPath, $dataDir);
    if ($ogInteractiveMode) {
        $showBans = false;
        $ogInteractiveNeedMenu = true;
        $ogInteractivePauseBeforeMenu = true;
        goto OG_INTERACTIVE_MENU;
    }
    exit(0);
}

if ($whyIp !== null) {
    og_patch_cli_why_ip($offerPath, $dataDir, $whyIp);
    exit(0);
}

if ($watchMode) {
    og_patch_cli_watch_logs($offerPath, $dataDir);
    exit(0);
}

if ($recoveryMode) {
    og_patch_cli_recovery($offerPath, $dryRun);
    exit(0);
}

if ($verifyMode) {
    og_patch_cli_verify($offerPath, $dataDir, $backupDir);
    exit(0);
}

if ($resetStateMode) {
    head('RESET STATE — повне обнулення накопиченого стану bot-protect');
    if (!is_dir($dataDir)) {
        warn('_og_data не існує: ' . $dataDir);
        exit(0);
    }
    $removed = 0;
    $patterns = [
        $dataDir . '/[a-f0-9]*.json',      // ip-state
        $dataDir . '/perm_ban.txt',         // perm_ban list
        $dataDir . '/bfp_*.json',           // browser fingerprints
        $dataDir . '/fp_*.json',            // device fingerprints
        $dataDir . '/abuse_*.json',         // abuseipdb cache
        $dataDir . '/geo_*.txt',            // geoip cache
        $dataDir . '/og_blocked.log',       // ban log
        $dataDir . '/og_runtime.log',       // runtime log
        $dataDir . '/og_traffic.log',       // traffic log
        $dataDir . '/og_sessions.log',      // sessions log
        $dataDir . '/og_access.log',        // access log
    ];
    foreach ($patterns as $pat) {
        foreach (@glob($pat) ?: [] as $f) {
            if (@unlink($f)) {
                $removed++;
                ok('Видалено: ' . basename($f));
            } else {
                warn('Не вдалося видалити: ' . $f);
            }
        }
    }
    // Зберігаємо whitelist.txt (його юзер вручну формував).
    info('whitelist.txt збережено (бан-стан незалежний від нього).');
    ok("✓ State обнулено. Видалено $removed файл(ів). bot-protect стартує з чистого листа.");
    info('Тепер відкрий сайт у браузері — будь-яких legacy банів немає.');
    exit(0);
}




if ($showWl) {
    og_patch_cli_show_whitelist($offerPath, $dataDir);
    if ($ogInteractiveMode) {
        $showWl = false;
        $ogInteractiveNeedMenu = true;
        $ogInteractivePauseBeforeMenu = true;
        goto OG_INTERACTIVE_MENU;
    }
    exit(0);
}




if ($unbanIp !== null) {
    og_patch_cli_unban_ip($offerPath, $dataDir, $unbanIp);
    if ($ogInteractiveMode) {
        $unbanIp = null;
        $ogInteractiveNeedMenu = true;
        $ogInteractivePauseBeforeMenu = true;
        goto OG_INTERACTIVE_MENU;
    }
    exit(0);
}




if ($allowIp !== null) {
    og_patch_cli_allow_ip($offerPath, $dataDir, $allowIp);
    if ($ogInteractiveMode) {
        $allowIp = null;
        $ogInteractiveNeedMenu = true;
        $ogInteractivePauseBeforeMenu = true;
        goto OG_INTERACTIVE_MENU;
    }
    exit(0);
}




if ($denyIp !== null) {
    og_patch_cli_deny_ip($offerPath, $dataDir, $denyIp);
    if ($ogInteractiveMode) {
        $denyIp = null;
        $ogInteractiveNeedMenu = true;
        $ogInteractivePauseBeforeMenu = true;
        goto OG_INTERACTIVE_MENU;
    }
    exit(0);
}


function og_patch_normalize_host(string $host): string
{
    $h = strtolower(trim($host));
    if ($h === '') {
        return '';
    }
    if (str_contains($h, ':') && !str_contains($h, ']')) {
        $h = (string)(preg_replace('/:\d+$/', '', $h) ?? $h);
    }

    return $h;
}


function og_patch_host_apex(string $host): string
{
    $h = og_patch_normalize_host($host);
    if ($h !== '' && str_starts_with($h, 'www.')) {
        return substr($h, 4);
    }

    return $h;
}


function og_patch_host_matches_canonical(string $host, string $canonical): bool
{
    $host = og_patch_normalize_host($host);
    $canonical = og_patch_normalize_host($canonical);
    if ($canonical === '' || $host === '') {
        return false;
    }
    if (hash_equals($canonical, $host)) {
        return true;
    }
    $hostOnly = preg_replace('/:\d+$/', '', $host) ?? $host;
    $canonOnly = preg_replace('/:\d+$/', '', $canonical) ?? $canonical;
    if (hash_equals($canonOnly, $hostOnly)) {
        return true;
    }

    return hash_equals(og_patch_host_apex($hostOnly), og_patch_host_apex($canonOnly));
}

function og_patch_canonical_origin(string $canonicalHostLower): string
{
    $h = og_patch_normalize_host($canonicalHostLower);

    return $h === '' ? '' : 'https://' . $h;
}

function og_patch_url_is_skippable(string $url): bool
{
    $u = trim($url);
    if ($u === '' || $u[0] === '#') {
        return true;
    }

    return (bool)preg_match('#^(mailto:|tel:|javascript:|data:|blob:|about:)#i', $u);
}

function og_patch_resolve_canonical_asset_url(string $url, string $canonicalHostLower, string $docPath = '/'): string
{
    if (og_patch_url_is_skippable($url)) {
        return $url;
    }
    $canon = og_patch_normalize_host($canonicalHostLower);
    if ($canon === '') {
        return $url;
    }
    $origin = og_patch_canonical_origin($canon);
    $u = trim($url);
    if (str_starts_with($u, '//')) {
        $refHost = strtolower((string)(parse_url('https:' . $u, PHP_URL_HOST) ?: ''));
        $path = (string)(parse_url('https:' . $u, PHP_URL_PATH) ?: '/');
        $query = parse_url('https:' . $u, PHP_URL_QUERY);
        $frag = parse_url('https:' . $u, PHP_URL_FRAGMENT);
        if ($refHost !== '' && !og_patch_host_matches_canonical($refHost, $canon)) {
            $out = $origin . ($path !== '' ? $path : '/');
            if (is_string($query) && $query !== '') {
                $out .= '?' . $query;
            }
            if (is_string($frag) && $frag !== '') {
                $out .= '#' . $frag;
            }

            return $out;
        }

        return $u;
    }
    if (preg_match('#^https?://#i', $u)) {
        $refHost = strtolower((string)(parse_url($u, PHP_URL_HOST) ?: ''));
        if ($refHost !== '' && og_patch_host_matches_canonical($refHost, $canon)) {
            $path = (string)(parse_url($u, PHP_URL_PATH) ?: '/');
            $query = parse_url($u, PHP_URL_QUERY);
            $frag = parse_url($u, PHP_URL_FRAGMENT);
            $out = $origin . ($path !== '' ? $path : '/');
            if (is_string($query) && $query !== '') {
                $out .= '?' . $query;
            }
            if (is_string($frag) && $frag !== '') {
                $out .= '#' . $frag;
            }

            return $out;
        }

        return $u;
    }
    if (str_starts_with($u, '/')) {
        return $origin . $u;
    }
    $baseDir = rtrim(str_replace('\\', '/', dirname($docPath)), '/');
    if ($baseDir === '.' || $baseDir === '') {
        $baseDir = '';
    }
    $combined = ($baseDir !== '' ? $baseDir . '/' : '/') . $u;
    $parts = [];
    foreach (explode('/', $combined) as $seg) {
        if ($seg === '' || $seg === '.') {
            continue;
        }
        if ($seg === '..') {
            array_pop($parts);
            continue;
        }
        $parts[] = $seg;
    }

    return $origin . '/' . implode('/', $parts);
}


function og_patch_canonicalize_html_urls(string $html, string $canonicalHostLower, string $docPath = '/'): string
{
    $canon = og_patch_normalize_host($canonicalHostLower);
    if ($canon === '') {
        return $html;
    }
    $attrs = ['href', 'src', 'poster', 'data', 'action'];
    foreach ($attrs as $attr) {
        $html = preg_replace_callback(
            '/\s' . preg_quote($attr, '/') . '\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i',
            static function (array $m) use ($attr, $canon, $docPath): string {
                $q = $m[1][0] ?? '"';
                $val = ($m[2] ?? '') !== '' ? $m[2] : ((($m[3] ?? '') !== '') ? $m[3] : ($m[4] ?? ''));
                if (stripos($val, '[OfferGuard') !== false || stripos($val, 'data-og-enc-') !== false) {
                    return $m[0];
                }
                $new = og_patch_resolve_canonical_asset_url($val, $canon, $docPath);
                if ($new === $val) {
                    return $m[0];
                }

                return ' ' . $attr . '=' . $q . $new . $q;
            },
            $html
        ) ?? $html;
    }
    $html = preg_replace_callback(
        '/\ssrcset\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i',
        static function (array $m) use ($canon, $docPath): string {
            $q = $m[1][0] ?? '"';
            $raw = ($m[2] ?? '') !== '' ? $m[2] : ((($m[3] ?? '') !== '') ? $m[3] : ($m[4] ?? ''));
            $parts = preg_split('/\s*,\s*/', $raw) ?: [];
            $out = [];
            foreach ($parts as $part) {
                $bits = preg_split('/\s+/', trim($part), 2);
                $u = $bits[0] ?? '';
                $desc = $bits[1] ?? '';
                $nu = og_patch_resolve_canonical_asset_url($u, $canon, $docPath);
                $out[] = trim($nu . ($desc !== '' ? ' ' . $desc : ''));
            }

            return ' srcset=' . $q . implode(', ', $out) . $q;
        },
        $html
    ) ?? $html;
    $html = og_patch_map_style_tags($html, static function (string $attrs, string $inner) use ($canon, $docPath): string {
        if (stripos($attrs, '[OfferGuard') !== false) {
            return '<style' . $attrs . '>' . $inner . '</style>';
        }
        $inner2 = preg_replace_callback(
            '/url\(\s*("|\')?([^"\')\s]+)\1?\s*\)/i',
            static function (array $m) use ($canon, $docPath): string {
                $u = trim((string)($m[2] ?? ''));
                $nu = og_patch_resolve_canonical_asset_url($u, $canon, $docPath);

                return 'url("' . $nu . '")';
            },
            $inner
        ) ?? $inner;

        return '<style' . $attrs . '>' . $inner2 . '</style>';
    });

    return $html;
}


function og_patch_neutralize_mirror_relative_refs(string $html): string
{
    return preg_replace_callback(
        '/\s(href|src|action)\s*=\s*(["\'])(\/[^"\']*)\2/i',
        static function (array $m): string {
            $attr = $m[1];
            $q = $m[2];
            $path = $m[3];
            if (preg_match('#^/(?:__OG_ASSETS_SUBDIR__/|_og_|bot-protect\.php|_site/|download-site|mirror-site|\.well-known/)#i', $path)) {
                return $m[0];
            }

            return ' ' . $attr . '=' . $q . 'about:invalid#og-mirror-' . rawurlencode(ltrim($path, '/')) . $q
                . ' data-og-mirror-neutralized="1" data-og-was' . $attr . '=' . $q . $path . $q;
        },
        $html
    ) ?? $html;
}


function og_patch_inject_copy_csp_meta(string $html, string $canonicalHostLower): string
{
    $lc = og_patch_normalize_host($canonicalHostLower);
    if ($lc === '') {
        return $html;
    }
    if (preg_match('/http-equiv\s*=\s*["\']Content-Security-Policy/i', $html)) {
        return $html;
    }
    $apex = og_patch_host_apex($lc);
    $hosts = array_values(array_unique(array_filter([$lc, $apex, 'www.' . $apex])));
    $fa = implode(' ', array_map(static fn(string $h): string => 'https://' . $h, $hosts));
    // frame-ancestors is NOT honoured in <meta> CSP (browsers ignore it and log a console error).
    // Only base-uri and form-action are valid meta-CSP directives.
    $csp = "base-uri 'self' " . $fa . "; form-action 'self' " . $fa;
    $tag = '<meta http-equiv="Content-Security-Policy" content="' . htmlspecialchars($csp, ENT_QUOTES, 'UTF-8') . '">';

    return og_patch_safe_inject_before_head_close($html, $tag);
}


function og_patch_copy_noscript_block(): string
{
    return '<!-- [OfferGuard:noscript-harden] -->'
        . '<noscript><meta http-equiv="refresh" content="0;url=about:blank">'
        . '<style>html,body{margin:0!important;padding:0!important;min-height:100vh;background:#fff!important}'
        . '#og-content,body>:not(noscript){display:none!important;visibility:hidden!important;height:0!important;overflow:hidden!important}</style>'
        . '<p style="position:absolute;left:-9999px">Enable JavaScript</p></noscript>'
        . '<!-- [/OfferGuard:noscript-harden] -->';
}

function og_patch_inject_copy_noscript_harden(string $html): string
{
    $block = og_patch_copy_noscript_block();
    if (str_contains($html, '[OfferGuard:noscript-harden]')) {
        $html = preg_replace('/<!--\s*\[OfferGuard:noscript-harden\]\s*-->.*?<!--\s*\[\/OfferGuard:noscript-harden\]\s*-->\s*/is', '', $html) ?? $html;
    }
    $parts = og_patch_split_html_by_scripts($html);
    foreach ($parts as $i => $chunk) {
        if ($i % 2 === 1) continue;
        if (preg_match('/<body\b[^>]*>/i', $chunk, $m, PREG_OFFSET_CAPTURE)) {
            $pos = $m[0][1] + strlen($m[0][0]);
            $parts[$i] = substr($chunk, 0, $pos) . "\n" . $block . substr($chunk, $pos);
            return implode('', $parts);
        }
    }

    return $block . "\n" . $html;
}


if (!function_exists('og_patch_js_host_match_fn')) {
    function og_patch_js_host_match_fn(): string
    {
        
        return 'function _ogHm(a,e){a=String(a||"").toLowerCase();e=String(e||"").toLowerCase();'
            . 'if(!e)return true;if(!a)return false;if(a===e)return true;'
            . 'function w(h){return h.indexOf("www.")===0?h.slice(4):h;}'
            . 'a=w(a.replace(/:\\d+$/,""));e=w(e.replace(/:\\d+$/,""));'
            . 'return a===e;}';
    }
}


if (!function_exists('og_patch_js_definitive_copy_fn')) {
    function og_patch_js_definitive_copy_fn(): string
    {
        return 'function _ogDefCopy(e){e=String(e||"").trim().toLowerCase();if(!e)return false;'
            . 'if(String(location.protocol||"")==="file:")return true;'
            . 'var lh=String(location.hostname||"").toLowerCase(),lhn=String(location.host||"").toLowerCase();'
            . 'if(lh&&_ogHm(lh,e))return false;if(lhn&&_ogHm(lhn,e))return false;'
            . 'try{var oh=String((location.origin&&location.origin!=="null")?new URL(location.origin).hostname:"").toLowerCase();'
            . 'if(oh&&_ogHm(oh,e))return false;}catch(x){}'
            . 'return true;}';
    }
}


if (!function_exists('og_patch_js_copy_beacon_fn')) {
    function og_patch_js_copy_beacon_fn(): string
    {
        return 'function _ogCopyBeacon(f){try{var eps=["bot-protect.php?_og_ep=v","../bot-protect.php?_og_ep=v","../../bot-protect.php?_og_ep=v"];'
            . 'for(var i=0;i<eps.length;i++){var u;try{u=new URL(eps[i],location.href).href;}catch(e){continue;}'
            . 'var fd=new FormData();fd.append("f",String(f||"copy_self_destruct"));'
            . 'if(navigator.sendBeacon&&navigator.sendBeacon(u,fd))break;}}catch(x){}}';
    }
}


if (!function_exists('og_patch_js_purge_legacy_copy_storage_fn')) {
    function og_patch_js_purge_legacy_copy_storage_fn(): string
    {
        return 'function _ogPurgeLegacyCopyStorage(e){try{sessionStorage.removeItem("_ogLt5");'
            . 'sessionStorage.removeItem("_ogT1");'
            . 'e=String(e||"").trim().toLowerCase();'
            . 'if(e){var sfx=e.replace(/[^\\w.-]+/g,"_");'
            . 'sessionStorage.removeItem("_ogLt5@"+sfx);sessionStorage.removeItem("_ogT1@"+sfx);}'
            . '}catch(x){}}';
    }
}


if (!function_exists('og_patch_js_origin_bypass_fn')) {
    function og_patch_js_origin_bypass_fn(): string
    {
        return 'function _ogCanonHostMatch(eh){eh=String(eh||"").trim().toLowerCase();if(!eh)return false;'
            . 'var lh=String(location.hostname||"").toLowerCase(),lhn=String(location.host||"").toLowerCase();'
            . 'if(lh&&_ogHm(lh,eh)||lhn&&_ogHm(lhn,eh))return true;'
            . 'try{var oh=String((location.origin&&location.origin!=="null")?new URL(location.origin).hostname:"").toLowerCase();'
            . 'if(oh&&_ogHm(oh,eh))return true;}catch(x){}return false;}'
            . 'function _ogOriginSessionOk(eh){try{var m=document.querySelector(\'meta[name="og-origin-session"]\');'
            . 'var tok=m&&String(m.getAttribute("content")||"").trim();'
            . 'if(!tok||tok.length<12)return false;'
            . 'eh=String(eh||"").trim().toLowerCase();if(!eh)return false;'
            . 'var pr=String(location.protocol||"");'
            . 'if(pr!=="https:"&&pr!=="http:")return false;'
            . 'var lh=String(location.hostname||"").toLowerCase();'
            . 'return !!(lh&&_ogHm(lh,eh));}catch(e){}return false;}'
            . 'function _ogOriginGraceOk(eh){if(_ogOriginSessionOk(eh))return true;'
            . 'if(!_ogCanonHostMatch(eh))return false;'
            . 'if(window.__ogOriginGraceT==null)window.__ogOriginGraceT=Date.now();'
            . 'if(Date.now()-window.__ogOriginGraceT<3000)return true;'
            . 'try{var m=document.querySelector(\'meta[name="og-origin-session"]\');'
            . 'var tok=m&&String(m.getAttribute("content")||"").trim();'
            . 'if(tok&&tok.length>=12)return true;}catch(x){}return true;}'
            . 'function _ogGateMayKill(eh){if(window.__ogCopyKilled)return true;'
            . 'eh=String(eh||"").trim().toLowerCase();'
            . 'if(!eh){try{var om=document.querySelector(\'meta[name="og-origin-host"]\');'
            . 'if(om)eh=String(om.getAttribute("content")||"").trim().toLowerCase();}catch(x){}}'
            . 'if(!eh)return false;'
            . 'if(String(location.protocol||"")==="file:")return true;'
            . 'if(_ogCanonHostMatch(eh)||_ogOriginGraceOk(eh))return false;'
            . 'if(_ogOriginSessionOk(eh))return false;'
            . 'return true;}';
    }
}


function og_patch_copy_css_origin_snippet(string $canonicalHostLower): string
{
    $lc = strtolower(trim($canonicalHostLower));
    if ($lc === '') {
        return '';
    }

    return '<!-- [OfferGuard:copy-css] -->'
        . '<style id="og-origin-css">:root{--og-canonical-host:"'
        . htmlspecialchars($lc, ENT_QUOTES, 'UTF-8')
        . '"}</style>'
        . '<!-- [/OfferGuard:copy-css] -->' . "\n";
}


function og_patch_neutralize_script_endings(string $js): string
{
    return (string)(preg_replace('#</script#i', '<\/script', $js) ?? $js);
}


function og_patch_wrap_inline_script(string $js, string $id = ''): string
{
    $attrs = $id !== '' ? ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '';
    return '<script' . $attrs . '>' . og_patch_neutralize_script_endings($js) . '</script>';
}


function og_patch_harden_script_blocks_in_html(string $html): string
{
    if ($html === '' || stripos($html, '<script') === false) {
        return $html;
    }
    return (string)preg_replace_callback(
        '/(<script\b[^>]*>)([\s\S]*?)(<\/script\s*>)/i',
        static function (array $m): string {
            return ($m[1] ?? '<script>')
                . og_patch_neutralize_script_endings((string)($m[2] ?? ''))
                . ($m[3] ?? '</script>');
        },
        $html
    );
}


/**
 * Split HTML into script/style islands and non-script chunks.
 * Returns array where odd indices are script/style tags (verbatim), even indices are plain HTML.
 */
function og_patch_split_html_by_scripts(string $html): array
{
    $parts = preg_split(
        '/(<script\b[^>]*>[\s\S]*?<\/script\s*>|<style\b[^>]*>[\s\S]*?<\/style\s*>)/i',
        $html,
        -1,
        PREG_SPLIT_DELIM_CAPTURE
    );
    return is_array($parts) ? $parts : [$html];
}

/**
 * Inject $insertion immediately before the first </head> that lives OUTSIDE any <script>/<style>.
 * Prevents corruption of JS string literals containing the substring "</head>".
 */
function og_patch_safe_inject_before_head_close(string $html, string $insertion): string
{
    $parts = og_patch_split_html_by_scripts($html);
    foreach ($parts as $i => $chunk) {
        if ($i % 2 === 1) continue;
        if (preg_match('#</head\s*>#i', $chunk)) {
            $parts[$i] = preg_replace('#</head\s*>#i', $insertion . "\n</head>", $chunk, 1) ?? $chunk;
            return implode('', $parts);
        }
    }
    return $html;
}

/**
 * Same but for </body>.
 */
function og_patch_safe_inject_before_body_close(string $html, string $insertion): string
{
    $parts = og_patch_split_html_by_scripts($html);
    foreach ($parts as $i => $chunk) {
        if ($i % 2 === 1) continue;
        if (preg_match('#</body\s*>#i', $chunk)) {
            $parts[$i] = preg_replace('#</body\s*>#i', $insertion . "\n</body>", $chunk, 1) ?? $chunk;
            return implode('', $parts);
        }
    }
    return $html . "\n" . $insertion;
}

/**
 * Replace </body> with $replacement (non-suffix-style), only outside scripts.
 */
function og_patch_safe_replace_body_close(string $html, string $replacement): string
{
    $parts = og_patch_split_html_by_scripts($html);
    foreach ($parts as $i => $chunk) {
        if ($i % 2 === 1) continue;
        if (stripos($chunk, '</body>') !== false) {
            $parts[$i] = str_ireplace('</body>', $replacement, $chunk);
            return implode('', $parts);
        }
    }
    return $html;
}


function og_patch_copy_live_defense_snippet(string $canonicalHostLower, int $originDeferMs = 2000): string
{
    $lc = strtolower(trim($canonicalHostLower));
    if ($lc === '') {
        return '';
    }
    $ehJs = json_encode($lc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $hmFn = og_patch_js_host_match_fn();
    $defFn = og_patch_js_definitive_copy_fn();
    $bypassFn = og_patch_js_origin_bypass_fn();
    $beaconFn = og_patch_js_copy_beacon_fn();
    $purgeFn = og_patch_js_purge_legacy_copy_storage_fn();
    $deferMs = max(0, $originDeferMs);
    $js = '(function(){if(window.__ogCopyKilled||window.__ogCopyLiveDead)return;'
        . $hmFn . $defFn . $bypassFn . $beaconFn . $purgeFn
        . 'var EH=' . $ehJs . ',pollF=0,perm=0,maxF=4,pollIv=null,pollMs=4000,deferMs=' . $deferMs . ';'
        . 'function expHost(){var h=String(EH||"").trim().toLowerCase();if(h)return h;'
        . 'try{var m=document.querySelector(\'meta[name="og-origin-host"]\');'
        . 'if(m){h=String(m.getAttribute("content")||"").trim().toLowerCase();if(h)return h;}}catch(e){}return"";}'
        . 'function copyCtx(){if(String(location.protocol||"")==="file:")return true;'
        . 'var e=expHost();if(!e)return false;return _ogDefCopy(e);}'
        . 'function scopedSk(b){b=String(b||"_ogLt5").trim();var e=expHost();return e?b+"@"+e.replace(/[^\\w.-]+/g,"_"):b;}'
        . 'function permKill(){if(perm)return;perm=1;window.__ogCopyLiveDead=1;window.__ogCopyKilled=1;'
        . 'try{_ogCopyBeacon("copy_self_destruct");}catch(_ogCb){}'
        . 'if(pollIv){try{clearInterval(pollIv);}catch(x){}pollIv=null;}'
        . 'try{sessionStorage.removeItem("_ogLt5");sessionStorage.removeItem(scopedSk("_ogLt5"));'
        . 'sessionStorage.removeItem(scopedSk("_ogT1"));}catch(x2){}'
        . 'try{var o=document.getElementById("og-content");if(o){o.innerHTML="";'
        . 'o.style.cssText="display:none!important;visibility:hidden!important;opacity:0!important";}}catch(x3){}'
        . 'try{document.documentElement.innerHTML="";document.documentElement.style.background="#fff";}catch(x4){}}'
        . 'function probeTok(){if(!copyCtx()||perm)return;'
        . 'try{var el=document.querySelector("._og_live_tok,input[name=og_live_token]");'
        . 'if(el&&String(el.value||"").length>8){pollF=maxF;permKill();return;}'
        . 'var sk=scopedSk("_ogLt5");'
        . 'if(sessionStorage.getItem("_ogLt5")&&!sessionStorage.getItem(sk)){pollF=maxF;permKill();return;}'
        . 'var v=sessionStorage.getItem(sk)||"";'
        . 'if(v.length>8&&!/^og/i.test(v)){pollF=maxF;permKill();}}catch(x5){}}'
        . 'function b64u(s){try{return btoa(unescape(encodeURIComponent(s))).replace(/\\+/g,"-").replace(/\\//g,"_").replace(/=+$/,"");}catch(e){return"";}}'
        . 'function probeLive(){if(perm||!copyCtx())return;'
        . 'var payload={a:"issue",h:location.host,o:location.origin,u:location.pathname+location.search,t:Date.now(),r:"cp",fp:"",tid:""};'
        . 'var body=JSON.stringify({p:b64u(JSON.stringify(payload))});'
        . 'var eps=["/_site/s","/bot-protect.php?_og_ep=s","bot-protect.php?_og_ep=s"],i=0;'
        . 'function next(){if(perm||i>=eps.length){pollF++;if(pollF>=maxF)permKill();return;}'
        . 'var u;try{u=new URL(eps[i++],location.href).href;}catch(e){next();return;}'
        . 'fetch(u,{method:"POST",credentials:"same-origin",cache:"no-store",headers:{"Content-Type":"application/json"},body:body})'
        . '.then(function(r){return r.text().then(function(t){var j={};try{j=t?JSON.parse(t):{};}catch(x){}'
        . 'var raw="";try{var s=String(j.p||"").replace(/-/g,"+").replace(/_/g,"/");while(s.length%4)s+="=";'
        . 'raw=decodeURIComponent(escape(atob(s)));}catch(x2){}'
        . 'var p={};try{p=raw?JSON.parse(raw):{};}catch(x3){}'
        . 'var reason=String(p.reason||"");'
        . 'if(p.copy_rejected||p.family==="denied"||reason==="copy_not_family"){if(isOrigin())return;pollF=maxF;permKill();return;}'
        . 'if(p.ok===false&&(p.blocked||/^(copy_rejected|copy_host_denied|copy_not_family|tab_limit|live_token)/.test(reason))){if(isOrigin())return;pollF=maxF;permKill();return;}'
        . 'if(p.ok===true&&p.family&&p.family!=="accepted"){if(isOrigin())return;pollF=maxF;permKill();return;}'
        . 'pollF++;if(pollF>=maxF&&!isOrigin())permKill();})["catch"](function(){next();});});}'
        . 'next();}'
        . 'if(typeof window.fetch==="function"&&!window.__ogCanonMintTrap){window.__ogCanonMintTrap=1;'
        . 'var _of=window.fetch;window.fetch=function(inp,init){if(copyCtx()){try{'
        . 'var u=typeof inp==="string"?inp:(inp&&inp.url)||"";var abs=new URL(u,location.href);'
        . 'var lh=String(location.hostname||"").toLowerCase();'
        . 'if(abs.protocol.indexOf("http")===0&&_ogHm(abs.hostname,EH)&&!_ogHm(lh,EH)){'
        . 'window.__ogCopyMintTrap=1;return Promise.reject(new TypeError("copy_mint_cors"));}'
        . '}catch(x6){}}return _of.apply(this,arguments);};}'
        . 'function isOrigin(){var eh=expHost();return !!(eh&&(_ogCanonHostMatch(eh)||_ogOriginSessionOk(eh)||_ogOriginGraceOk(eh)));}'
        . 'function tick(){if(perm)return;if(isOrigin()){_ogPurgeLegacyCopyStorage(expHost());return;}'
        . 'var eh=expHost();if(eh&&_ogCanonHostMatch(eh))return;'
        . 'if(copyCtx()){probeTok();probeLive();}}'
        . 'function armPoll(){if(pollIv||perm||isOrigin())return;pollIv=setInterval(tick,pollMs);setTimeout(tick,400);setTimeout(tick,1200);}'
        . 'if(isOrigin()){_ogPurgeLegacyCopyStorage(expHost());setTimeout(function(){if(!isOrigin())armPoll();},deferMs);}'
        . 'else{tick();var eh0=expHost();if(!eh0||!_ogCanonHostMatch(eh0))armPoll();}'
        . 'document.addEventListener("visibilitychange",tick);window.addEventListener("pageshow",tick);})();';

    return '<!-- [OfferGuard:copy-live] -->'
        . og_patch_wrap_inline_script($js, 'og-copy-live-defense')
        . '<!-- [/OfferGuard:copy-live] -->';
}


function og_patch_copy_reject_head_snippet(string $canonicalHostLower): string
{
    $lc = strtolower(trim($canonicalHostLower));
    if ($lc === '') {
        return '';
    }
    $ehJs = json_encode($lc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $hmFn = og_patch_js_host_match_fn();
    $defFn = og_patch_js_definitive_copy_fn();
    $bypassFn = og_patch_js_origin_bypass_fn();
    $beaconFn = og_patch_js_copy_beacon_fn();
    $purgeFn = og_patch_js_purge_legacy_copy_storage_fn();
    $js = '(function(){if(window.__ogCopyRejected||window.__ogCopyKilled)return;'
        . $hmFn . $defFn . $bypassFn . $beaconFn . $purgeFn
        . 'var EH=' . $ehJs . ',perm=0,famOk=0,timers=[];'
        . 'var _si=window.setInterval,_st=window.setTimeout;'
        . 'window.setInterval=function(){if(perm)return 0;timers.push(_si.apply(this,arguments));return timers[timers.length-1];};'
        . 'window.setTimeout=function(){if(perm)return 0;timers.push(_st.apply(this,arguments));return timers[timers.length-1];};'
        . 'function expHost(){var h=String(EH||"").trim().toLowerCase();if(h)return h;'
        . 'try{var m=document.querySelector(\'meta[name="og-origin-host"]\');'
        . 'if(m){h=String(m.getAttribute("content")||"").trim().toLowerCase();if(h)return h;}}catch(e){}return"";}'
        . 'function scopedSk(b){b=String(b||"_ogLt5").trim();var e=expHost();return e?b+"@"+e.replace(/[^\\w.-]+/g,"_"):b;}'
        . 'function familyBad(p){if(!p||typeof p!=="object")return true;'
        . 'if(p.copy_rejected||p.family==="denied"||p.accepted===false)return true;'
        . 'var r=String(p.reason||"");'
        . 'if(/^(copy_rejected|copy_host_denied|copy_not_family)/.test(r))return true;'
        . 'if(p.ok===true&&p.family&&p.family!=="accepted")return true;return false;}'
        . 'function markReject(){try{window.__ogCopyRejected=1;document.documentElement.dataset.ogCopy="rejected";}catch(x){}}'
        . 'function stopTimers(){perm=1;try{for(var i=0;i<timers.length;i++){clearInterval(timers[i]);clearTimeout(timers[i]);}}catch(x2){}'
        . 'try{var mx=setInterval(function(){},1);for(var j=0;j<=mx+64;j++){clearInterval(j);clearTimeout(j);}}catch(x3){}}'
        . 'function nuclear(){if(perm)return;perm=1;markReject();window.__ogCopyKilled=1;stopTimers();'
        . 'try{_ogCopyBeacon("copy_self_destruct");}catch(_ogCb){}'
        . 'try{sessionStorage.removeItem("_ogLt5");sessionStorage.removeItem(scopedSk("_ogLt5"));'
        . 'sessionStorage.removeItem(scopedSk("_ogT1"));}catch(x4){}'
        . 'try{var o=document.getElementById("og-content");if(o){o.innerHTML="";'
        . 'o.style.cssText="display:none!important;visibility:hidden!important;opacity:0!important";}}catch(x5){}'
        . 'var b="<!DOCTYPE html><html><head><meta charset=UTF-8><title></title>"'
        . '+"<style>html,body{margin:0!important;background:#fff!important}</style></head><body></body></html>";'
        . 'try{document.open("text/html","replace");document.write(b);document.close();}catch(x6){'
        . 'try{document.documentElement.innerHTML=b;}catch(x7){}}}'
        . 'function localCopy(){if(String(location.protocol||"")==="file:")return true;'
        . 'var e=expHost();if(!e)return false;if(_ogOriginSessionOk(e))return false;return _ogDefCopy(e);}'
        . 'function storageCopy(){var e=expHost();if(e&&(_ogCanonHostMatch(e)||_ogOriginSessionOk(e)||_ogOriginGraceOk(e)))return false;'
        . 'try{var sk=scopedSk("_ogLt5");'
        . 'if(sessionStorage.getItem("_ogLt5")&&!sessionStorage.getItem(sk))return true;'
        . 'var v=sessionStorage.getItem(sk)||"";if(v.length>8&&!/^og/i.test(v))return true;}catch(x8){}return false;}'
        . 'function b64u(s){try{return btoa(unescape(encodeURIComponent(s))).replace(/\\+/g,"-").replace(/\\//g,"_").replace(/=+$/,"");}catch(e){return"";}}'
        . 'function probeFamily(){if(perm||famOk||!localCopy())return;var eh=expHost();if(eh&&(_ogCanonHostMatch(eh)||_ogOriginSessionOk(eh)||_ogOriginGraceOk(eh)))return;'
        . 'var payload={a:"issue",h:location.host,o:location.origin,u:location.pathname+location.search,t:Date.now(),r:"cf",fp:"",tid:""};'
        . 'var body=JSON.stringify({p:b64u(JSON.stringify(payload))});'
        . 'var eps=["/_site/s","/bot-protect.php?_og_ep=s","bot-protect.php?_og_ep=s"],i=0;'
        . 'function next(){if(perm||famOk||i>=eps.length)return;'
        . 'var u;try{u=new URL(eps[i++],location.href).href;}catch(e){next();return;}'
        . 'fetch(u,{method:"POST",credentials:"same-origin",cache:"no-store",headers:{"Content-Type":"application/json"},body:body})'
        . '.then(function(r){return r.text().then(function(t){var j={};try{j=t?JSON.parse(t):{};}catch(x){}'
        . 'var raw="";try{var s=String(j.p||"").replace(/-/g,"+").replace(/_/g,"/");while(s.length%4)s+="=";'
        . 'raw=decodeURIComponent(escape(atob(s)));}catch(x2){}'
        . 'var p={};try{p=raw?JSON.parse(raw):{};}catch(x3){}'
        . 'if(!familyBad(p)&&(p.ok===true||p.family==="accepted"||p.accepted===true)){famOk=1;return;}'
        . 'if(familyBad(p)){if(eh&&(_ogCanonHostMatch(eh)||_ogOriginSessionOk(eh)||_ogOriginGraceOk(eh)))return;nuclear();return;}next();});})["catch"](function(){next();});}'
        . 'next();}'
        . 'function tick(){if(perm)return;var eh=expHost();'
        . 'if(eh&&(_ogCanonHostMatch(eh)||_ogOriginSessionOk(eh)||_ogOriginGraceOk(eh))){_ogPurgeLegacyCopyStorage(eh);return;}'
        . 'if(localCopy()||storageCopy())nuclear();}'
        . 'var eh0=expHost();if(eh0&&(_ogCanonHostMatch(eh0)||_ogOriginSessionOk(eh0)||_ogOriginGraceOk(eh0))){_ogPurgeLegacyCopyStorage(eh0);}'
        . 'else{tick();probeFamily();var iv=_si(function(){if(!famOk){tick();probeFamily();}},2000);timers.push(iv);}'
        . 'document.addEventListener("visibilitychange",tick);window.addEventListener("pageshow",tick);})();';

    return '<!-- [OfferGuard:copy-reject] -->'
        . og_patch_wrap_inline_script($js, 'og-copy-reject')
        . '<!-- [/OfferGuard:copy-reject] -->';
}


function og_patch_mirror_probe_trap_paths(): array
{
    return ['/.well-known/og-mirror-probe', '/_og_mirror_probe'];
}




$BOT_PROTECT = <<<'BP'
<?php
// PHP 7.4 polyfills (generated bot-protect must run without patch.php loaded)
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strpos($haystack, $needle) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }
        $len = strlen($needle);
        return $len <= strlen($haystack) && substr($haystack, -$len) === $needle;
    }
}
// ── XLOG bootstrap (вне IIFE — чтобы был доступен в любом месте) ─────────────
// og_xlog_bind() вызывается из main flow сразу после $C; og_xlog() — везде.
function og_xlog_bind(array $C): void
{
    $GLOBALS['__og_xlog_cfg'] = [
        'on' => !empty($C['xlog_on']),
        'level' => strtolower((string)($C['xlog_level'] ?? 'info')),
        'dir' => (string)($C['dir'] ?? (__DIR__ . '/_og_data')),
        'max' => (int)($C['xlog_max_bytes'] ?? 10485760),
    ];
}
function og_xlog(string $level, string $fn, string $msg, array $ctx = [], string $src = 'srv'): void
{
    $cfg = $GLOBALS['__og_xlog_cfg'] ?? null;
    if (!is_array($cfg) || empty($cfg['on'])) return;
    static $rank = ['debug' => 10, 'info' => 20, 'warn' => 30, 'error' => 40, 'fatal' => 50];
    $cur = $rank[strtolower($level)] ?? 20;
    $min = $rank[$cfg['level'] ?? 'info'] ?? 20;
    if ($cur < $min) return;
    $dir = (string)$cfg['dir'];
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $file = $dir . '/og_xlog.log';
    $maxBytes = (int)$cfg['max'];
    if (is_file($file) && @filesize($file) > $maxBytes) {
        @rename($file, $file . '.' . date('YmdHis') . '.bak');
    }
    if (empty($GLOBALS['__og_xrid'])) {
        try { $GLOBALS['__og_xrid'] = bin2hex(random_bytes(4)); }
        catch (Throwable $e) { $GLOBALS['__og_xrid'] = substr(md5(uniqid('', true)), 0, 8); }
    }
    $rid = (string)$GLOBALS['__og_xrid'];
    $ip = (string)($GLOBALS['__og_xlog_ip'] ?? ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'));
    $uri = (string)($_SERVER['REQUEST_URI'] ?? '-');
    $uri = substr(preg_replace('/[^\x20-\x7e]/', '?', $uri) ?? $uri, 0, 220);
    $ts = date('Y-m-d\TH:i:s.') . sprintf('%03d', (int)(microtime(true) * 1000) % 1000) . 'Z';
    $sanIp = substr(preg_replace('/[^\x20-\x7e]/', '?', $ip) ?? $ip, 0, 45);
    $sanFn = substr(preg_replace('/[\|\r\n\t]/', '_', $fn) ?? $fn, 0, 60);
    $sanMsg = substr(preg_replace('/[\|\r\n\t]/', ' ', $msg) ?? $msg, 0, 500);
    $ctxStr = '';
    if (!empty($ctx)) {
        $enc = json_encode($ctx, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (is_string($enc)) {
            $enc = str_replace(["\r", "\n"], ['\\r', '\\n'], $enc);
            $ctxStr = '|ctx=' . substr($enc, 0, 1200);
        }
    }
    $line = sprintf("%s|%s|%s|%s|%s|%s|fn=%s|msg=%s%s\n",
        $ts, strtoupper($level), $src, $rid, $sanIp, $uri, $sanFn, $sanMsg, $ctxStr);
    @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
}

/**
 * bot-protect.php — OfferGuard v6
 * DDoS · Crawling · SQLi · XSS · LFI · RFI · CMD
 * Cookie Challenge · Tor · Datacenter IP · Behavior Score
 * Timing Analysis · Suspect Score · Browser Fingerprint Headers
 * Session Integrity · JS Polygraphy · Math + PoW Challenge · Sec-Fetch (Chromium)
 * Invisible Preflight · Live Page Token · Encrypted Remote Gate
 * GeoIP (опционально) · IP Reputation (опционально)
 * PHP 7.4+ · без Nginx · без БД
 */
(function () {

// ══════════════════════════════════════════════════════════════════
//  НАСТРОЙКИ
// ══════════════════════════════════════════════════════════════════
$C = [
    // Rate-limit (sliding window)
    'rl_sec'          => 24,       // origin: burst HTML+preflight+assets+live; копии режутся copy-guard раньше
    'rl_min'          => 120,      // до 120 запросов в минуту
    'rl_hour'         => 600,      // до 600 запросов в час
    'rl_block'        => 900,      // 15 мин блок за rl-нарушение, чтобы не запирать живого юзера на часы
    'atk_block'       => 1209600,  // 14 дн за атаку

    // Хранилище
    'dir'             => __DIR__ . '/_og_data',
    'log'             => __DIR__ . '/_og_data/og_blocked.log',
    'log_on'          => true,
    'log_max_bytes'   => 5 * 1024 * 1024,

    // Traffic log — каждый запрос
    'traffic_log'     => __DIR__ . '/_og_data/og_traffic.log',
    'traffic_log_on'  => true,
    'traffic_log_max' => 20 * 1024 * 1024,   // 20 MB — ротация

    // Sessions log — итог сессии при завершении/смене
    'sessions_log'    => __DIR__ . '/_og_data/og_sessions.log',
    'sessions_log_on' => true,
    'sessions_log_max'=> 10 * 1024 * 1024,

    // Детальный xlog (debug-уровень для tracing каждого шага: gate, decrypt, asset).
    // ВКЛЮЧЕНО по умолчанию — отдельный файл og_xlog.log с ротацией.
    // Уровни: debug < info < warn < error < fatal. На проде ставь 'info' или 'warn'.
    // Клиентский JS-гард шлёт ошибки сюда же через POST /_site/x (src='cli').
    'xlog_on'         => true,
    'xlog_level'      => 'debug',
    'xlog_max_bytes'  => 10 * 1024 * 1024,
    // Клиентский xlog: максимум сообщений на 1 IP в минуту (anti-flood).
    'xlog_client_rate_min' => 60,

    // Cookie challenge
    'cookie_name'     => '_ogv',
    'cookie_secret'   => 'OG_SECRET_CHANGE_ME',

    // Honeypot
    'hp'              => '_w',

    // Behavior score (из JS, 0..1)
    'bs_field'        => '_bs',
    'bs_min'          => 0.28,

    // HTML-навигация без JS-beacon (браузерные парсеры, «тихие» вкладки)
    'human_miss_max'      => 8,   // после стольких HTML-GET без human_ok подряд
    'human_miss_min_urls' => 12,  // при минимум стольких уникальных URL в сессии

    // Tor list
    'tor_list'        => __DIR__ . '/_og_data/tor_exits.txt',
    'tor_ttl'         => 21600,

    // GeoIP (оставить [] чтобы отключить; требует maxmind-db/reader + mmdb)
    'geo_block'       => [],
    'geo_db'          => __DIR__ . '/GeoLite2-Country.mmdb',

    // AbuseIPDB (оставить '' чтобы отключить)
    'abuseipdb_key'   => '',
    'abuseipdb_min'   => 80,

    // Datacenter CIDR
    'dc_cidrs' => [
        '3.0.0.0/8','18.0.0.0/8','52.0.0.0/8','54.0.0.0/8',
        '34.0.0.0/8','35.0.0.0/8',
        '20.0.0.0/8','40.0.0.0/8','104.208.0.0/13',
        '104.131.0.0/18','167.99.0.0/16','165.227.0.0/16',
        '116.203.0.0/16','159.69.0.0/16','78.46.0.0/15',
        '51.68.0.0/16','51.75.0.0/16','5.135.0.0/16',
        '45.33.0.0/16','45.56.0.0/14','66.175.208.0/20',
        '192.241.0.0/18','162.243.0.0/16',
        // VPN-провайдеры (CDN ranges не блокируем, чтобы не банить сайты за прокси/CDN edge)
        '185.220.0.0/16','185.107.0.0/16','198.54.0.0/15',
    ],

    // Fingerprint-токен (из JS)
    'fp_field'        => '_fp',

    // Эскалация банов
    'perm_ban_file'   => __DIR__ . '/_og_data/perm_ban.txt',
    'perm_ban_after'  => 3,
    'strike_decay'    => 86400,

    // Subnet-защита (/24)
    'subnet_mask_v4'  => 24,
    'subnet_limit_10m'=> 20,       // до 20 уникальных IP из одной /24 подсети за 10 мин
    'subnet_block'    => 86400,

    // Timing-анализ: детект ботов по равномерным интервалам
    'timing_window'   => 20,       // последних N запросов (підняв з 10 щоб не fp-ити heartbeat)
    'timing_stddev_min'=> 0.05,    // мін. стд.відхил. (с) — нижче = бот. Знижено з 0.15
                                    // (HTTP RTT jitter дає 0.05-0.10 навіть на «бот»-таймерах)
    'timing_block'    => 43200,    // 12 ч

    // Suspect score: накопительный счёт подозрений (0..100)
    'suspect_block'       => 85,   // бан только при наборе нескольких сильных сигналов
    'instant_block_score' => 70,   // мгновенно баним только явную автоматизацию/атаку
    'suspect_decay'       => 3600, // сброс через 1 ч без нарушений

    // Browser fingerprint headers
    'require_accept'         => true,
    'require_accept_language'=> true,
    'require_accept_encoding'=> true,

    // Session integrity
    'session_check'   => true,
    'session_max_url_jump' => 5,   // макс. прыжок глубины URL за 1 переход

    // Math challenge (невидимая задача при подозрении)
    'challenge_on_suspect' => 70,  // при suspect >= 70 выдавать challenge
    // PoW: SHA-256(nonce|counter) в hex начинается с N ведущих «0» (5 ≈ 1M итераций в JS, ~30–150ms)
    'challenge_pow_hex'    => 5,
    'challenge_nonce_ttl'  => 900,
    // Honeypot-поле на challenge-форме (должно остаться пустым)
    'challenge_hp_field'   => '_og_hp_company',

    // Chromium/Edge без Sec-Fetch на HTML GET — типично для headless и HTTP-клиентов с поддельным UA
    'sec_fetch_strict'     => false,

    // Мини-honeypot до ленда (невидимый кадр ~1–2 rAF): GET без cookie → белый экран + POST /_site/r; приманки /_site/m/*
    'preflight_on'         => false,
    'preflight_cookie'     => '_ogpf',
    'preflight_ttl'        => 86400,
    'preflight_nonce_ttl'  => 600,

    // Remote gate: центральный сервер может разрешить/оборвать показ ленда.
    // Запрос/ответ шифруются AES-256-GCM общим секретом. По умолчанию выключено.
    'remote_gate_on'        => false,
    'remote_gate_url'       => '',       // например: https://guard.example.com/og-gate
    'remote_gate_secret'    => 'OG_SECRET_CHANGE_ME',
    'remote_gate_timeout'   => 2,
    'remote_gate_cache_ttl' => 300,      // allow-кэш, чтобы не дергать сервер каждый GET
    'remote_gate_deny_ttl'  => 3600,     // deny-кэш: если сервер "оборвал", сайт не откроется
    'remote_gate_fail_closed' => false,  // true = при недоступном сервере закрывать ленд
    'remote_gate_send_ip'   => false,    // false = отправлять только HMAC-хэш IP

    // Live page token: HTML разблокируется только после короткого server-issued токена.
    // Запрос/ответ идут base64(JSON); секретная проверка остаётся на сервере.
    'live_token_on'       => true,
    'live_token_endpoint' => '/_site/s',
    'live_token_ttl'      => 120,
    'live_token_request_ttl' => 30,
    'live_token_confirm_required' => true,
    'live_session_ttl'    => 25,
    'live_payload_key'    => 'OG_PAYLOAD_KEY_CHANGE_ME',
    'live_token_cookie'   => '_oglive',
    // false нужно для чисто статических HTML-лендов: они не проходят PHP до загрузки JS.
    // Для PHP-only офферов можно вручную поставить true в сгенерированном bot-protect.php.
    'live_token_require_cookies' => false,

    // Split-key: статика зашифрована под K=HMAC(Kfrag|bucket|nonce, Kbase).
    // Kbase отдаётся в confirm, Kfrag — по каждому heartbeat, протухает за live_kfrag_ttl сек.
    // Без живого heartbeat-соединения с origin копия не соберёт ключ. Только зашифрованный путь.
    'live_key_split'      => false,
    'live_kfrag_ttl'      => 8,     // TTL фрагмента/heartbeat, сек (max-режим: коротко)
    'live_kfrag_grace_ms' => 2500,  // grace dead-man switch до необратимого вайпа
    'live_beat_max_fails' => 1,     // в max-режиме: 1 пропущенный beat = вайп
    'live_sse'            => true,  // SSE-стрим ключей с откатом на short-poll
    'live_beat_min_gap'   => 2,     // мин. секунд между beat одного sid (анти-релей)

    // Live session pub (opaque токен в sessionStorage + X-Og-Token; revoke через sendBeacon)
    // Webhook contract (bot-protect → your URL), see og_webhook_call():
    //   Outbound POST JSON: {event,token,ip,ts,sid?,host?}  event=mint|revoke|validate|rotate
    //   Header X-Og-Webhook-Secret when og_webhook_secret / OG_WEBHOOK_SECRET set.
    //   Authoritative response JSON: {ok:true,token?,exp?,ban?} or {ok:false,reason?}
    // og_webhook_mode: notify (default, origin-safe) | authoritative (mint/validate via webhook).
    // Origin-safe defaults: notify mode; validate errors fail-open; mint falls back to local pub unless strict.
    // og_webhook_strict_mint=false → confirm/rotate webhook down → local mint + warning (origin never bricked).
    // og_webhook_strict_mint=true  → mint webhook down → no g (paranoid deploys only).
    // og_webhook_fail_open_validate=true → validate timeout → trust local row (beat always fail-open on origin).
    // Copies (host ≠ canonical_host): validate never fail-open; mint never succeeds (canonical gate earlier).
    // OG_WEBHOOK_URL / OG_WEBHOOK_SECRET from env when empty here.
    'og_webhook_url'                 => '',
    'og_webhook_mode'                => 'notify',
    'og_webhook_secret'              => '',
    'og_webhook_timeout'             => 2,
    'og_webhook_validate'            => false,
    'og_webhook_validate_url'        => '',
    'og_webhook_mint_sync'           => true,
    'og_webhook_strict_mint'         => false,
    'og_webhook_fail_open'           => false,
    'og_webhook_fail_open_validate'  => true,
    'og_webhook_validate_read_timeout' => 1.5,

    // Live pub rotation (sessionStorage + X-Og-Token): TTL ~60s, refresh ~45s on origin.
    'live_pub_ttl'        => 60,
    'live_pub_rotate'     => true,

    // Бан IP при заведомо ложном live pub (X-Og-Token / og_live_token): непустой, но битый/чужой/revoke.
    // Пустой токен (legacy, сеть без заголовка) не баним. og_ban_bad_token_subnet_per_min — лимит банов с /24 в минуту (0 = без лимита).
    'og_ban_bad_token'                 => true,
    'og_ban_bad_token_ttl'             => 0,   // 0 = использовать atk_block
    'og_ban_bad_token_subnet_per_min'  => 12,

    // Reload grace: origin — до 3 stale/revoked pub в минуту без бана (4-й → ban). Копии — без grace.
    'live_pub_reload_grace_per_min'  => 3,
    'live_pub_reload_grace_window'   => 60,

    // 1 hook (host|origin|uri|challenge) = 1 вкладка = 1 live pub; вторая вкладка → tab_limit (копии — бан).
    'live_hook_one_tab'              => true,

    // Канонічний host ленда (patch: --canonical-host=). Порожньо = не перевіряти Host/Origin.
    'canonical_host'      => 'OG_CANONICAL_HOST_CHANGE_ME',

    // Адаптивный порог по размеру сайта
    // Система сама считает уникальные HTML-страницы сайта и адаптирует пороги.
    // scrape_pct_warn  — % охвата за сессию → +suspect (мягко)
    // scrape_pct_block — % охвата за сессию → бан
    // site_map_ttl     — как часто пересчитывать размер сайта (сек)
    // site_map_min     — минимум страниц для активации адаптивного режима
    'scrape_pct_warn'  => 40,   // 40% страниц за сессию → подозрение
    'scrape_pct_block' => 60,   // 60% страниц за сессию → бан
    'site_map_ttl'     => 3600, // пересчёт раз в час
    'site_map_min'     => 4,    // минимум 4 известных страницы для адаптации
];

// Прокидываем xlog-конфиг в глобальный регистр (см. og_xlog_bind() выше).
// Без этого функция-helper не видит $C из IIFE-скоупа и пишет молча no-op.
og_xlog_bind($C);

if (trim((string)($C['og_webhook_url'] ?? '')) === '') {
    $ogWhEnv = getenv('OG_WEBHOOK_URL');
    if (is_string($ogWhEnv) && trim($ogWhEnv) !== '') {
        $C['og_webhook_url'] = trim($ogWhEnv);
    }
}
if (trim((string)($C['og_webhook_secret'] ?? '')) === '') {
    $ogWhSecEnv = getenv('OG_WEBHOOK_SECRET');
    if (is_string($ogWhSecEnv) && trim($ogWhSecEnv) !== '') {
        $C['og_webhook_secret'] = trim($ogWhSecEnv);
    }
}
$ogWhMode = strtolower(trim((string)($C['og_webhook_mode'] ?? 'notify')));
$C['og_webhook_mode'] = in_array($ogWhMode, ['notify', 'authoritative'], true) ? $ogWhMode : 'notify';
if ($C['og_webhook_mode'] === 'authoritative' && trim((string)($C['og_webhook_url'] ?? '')) === '') {
    @error_log('[OfferGuard] og_webhook_mode=authoritative but og_webhook_url empty — downgraded to notify');
    $C['og_webhook_mode'] = 'notify';
}

// ══════════════════════════════════════════════════════════════════
//  ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// ══════════════════════════════════════════════════════════════════

function og_ip_in_cidr(string $ip, string $cidr): bool
{
    [$subnet, $bits] = explode('/', $cidr);
    $ip_l  = ip2long($ip);
    $sub_l = ip2long($subnet);
    if ($ip_l === false || $sub_l === false) return false;
    $mask = -1 << (32 - (int)$bits);
    return ($ip_l & $mask) === ($sub_l & $mask);
}

function og_save(array &$d, string $ipf): void
{
    $dir = dirname($ipf);
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
        @file_put_contents($dir . '/.htaccess', "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nOrder deny,allow\nDeny from all\n</IfModule>\n");
        @file_put_contents($dir . '/index.php', '<?php // silence');
    }
    file_put_contents($ipf, json_encode($d, JSON_UNESCAPED_UNICODE), LOCK_EX);
}

/** Acquire an exclusive per-IP lock. Returns filehandle (or false on failure). */
function og_ip_lock(string $ipf): mixed
{
    $fh = @fopen($ipf . '.lk', 'c');
    if ($fh !== false) flock($fh, LOCK_EX);
    return $fh;
}

/** Release a lock acquired with og_ip_lock(). */
function og_ip_unlock(mixed $fh): void
{
    if (is_resource($fh)) { flock($fh, LOCK_UN); fclose($fh); }
}

function og_http_get(string $url, array $headers = []): ?string
{
    $opts = ['timeout' => 3, 'ignore_errors' => true, 'follow_location' => false];
    if ($headers) $opts['header'] = implode("\r\n", $headers);
    $ctx = stream_context_create(['http' => $opts]);
    $r   = @file_get_contents($url, false, $ctx);
    return $r === false ? null : $r;
}

function og_http_post_json(string $url, array $payload, string $secret, int $timeout = 2): ?array
{
    $body = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($body === false) return null;

    $ts  = (string)time();
    $sig = hash_hmac('sha256', $ts . '.' . $body, $secret);
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'X-OfferGuard-Timestamp: ' . $ts,
        'X-OfferGuard-Signature: ' . $sig,
    ];
    $ctx = stream_context_create(['http' => [
        'method'        => 'POST',
        'timeout'       => max(1, $timeout),
        'ignore_errors' => true,
        'follow_location' => false,
        'header'        => implode("\r\n", $headers),
        'content'       => $body,
    ]]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false || $raw === '') return null;
    $json = json_decode($raw, true);
    return is_array($json) ? $json : null;
}

function og_log_field(string $value, int $limit = 240): string
{
    $value = preg_replace('/[\r\n|]+/', ' ', $value) ?? $value;
    $value = trim($value);
    if (strlen($value) > $limit) {
        $value = substr($value, 0, $limit - 3) . '...';
    }
    return $value === '' ? '-' : $value;
}

function og_safe_uri_for_log(string $uri): string
{
    $path = (string)(parse_url($uri, PHP_URL_PATH) ?: '/');
    $query = (string)(parse_url($uri, PHP_URL_QUERY) ?: '');
    if ($query === '') {
        return og_log_field($path);
    }
    $keys = [];
    foreach (explode('&', $query) as $part) {
        $key = urldecode((string)strtok($part, '='));
        if ($key !== '') {
            $keys[] = og_log_field($key, 48) . '=...';
        }
        if (count($keys) >= 8) {
            $keys[] = '...';
            break;
        }
    }
    return og_log_field($path . '?' . implode('&', $keys));
}

function og_runtime_log(string $file, string $ip, string $uri, string $msg): void
{
    @file_put_contents($file, date('Y-m-d H:i:s') . '|' . og_log_field($ip, 64) . '|' . og_safe_uri_for_log($uri) . '|' . og_log_field($msg, 500) . "\n", FILE_APPEND | LOCK_EX);
}

// (og_xlog_bind() и og_xlog() вынесены ВЫШЕ IIFE — см. начало файла. PHP не
//  hoist'ит вложенные функции до момента вызова, поэтому хелперам логирования
//  место в глобальном scope, чтобы они были доступны с первой строки IIFE.)

function og_ban_reason_log(string $file, string $ip, string $uri, string $type, string $reason, string $source, int $until = 0): void
{
    $msg = 'BAN|type=' . og_log_field($type, 32)
        . '|reason=' . og_log_field($reason)
        . '|source=' . og_log_field($source, 80);
    if ($until > 0) {
        $msg .= '|until=' . $until . '|ttl=' . max(0, $until - time());
    }
    og_runtime_log($file, $ip, $uri, $msg);
}

function og_mark_block(array &$d, string $type, int $until, string $reason, string $source, string $rtLog, string $ip, string $uri): void
{
    $field = $type === 'rl' ? 'rl_block' : 'atk_block';
    $d[$field] = $until;
    $d['ban_reason'] = $reason;
    $d['ban_source'] = $source;
    $d['ban_type'] = $type === 'rl' ? 'rl_block' : 'atk_block';
    $d['ban_until'] = $until;
    $d['ban_logged_at'] = time();
    og_ban_reason_log($rtLog, $ip, $uri, $d['ban_type'], $reason, $source, $until);
    og_xlog('warn', 'og_mark_block', 'ban_set', [
        'type' => $d['ban_type'],
        'reason' => $reason,
        'source' => $source,
        'ip' => $ip,
        'until' => $until,
        'ttl_sec' => max(0, $until - time()),
        'uri' => substr($uri, 0, 120),
    ]);
}

function og_starts_with(string $h, string $n): bool { return $n === '' || strpos($h, $n) === 0; }
function og_contains(string $h, string $n): bool    { return $n === '' || strpos($h, $n) !== false; }

function og_ip_prefix(string $ip, int $mask = 24): string
{
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return 'na';
    $parts = explode('.', $ip);
    if ($mask >= 24) return $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.0/24';
    if ($mask >= 16) return $parts[0] . '.' . $parts[1] . '.0.0/16';
    return $parts[0] . '.0.0.0/8';
}

function og_log_extended(string $log, string $ip, string $ua, string $uri,
                         string $reason, int $code, string $method, array $extra = []): void
{
    $line = implode('|', [
        date('Y-m-d H:i:s'), $ip, $reason, $code, $method,
        substr($ua, 0, 120), $uri,
        $_SERVER['HTTP_REFERER'] ?? '-',
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '-',
        $extra['suspect']    ?? '-',
        $extra['human_score']?? '-',
        $extra['timing_std'] ?? '-',
    ]);
    @file_put_contents($log, $line . "\n", FILE_APPEND | LOCK_EX);
}

function og_perm_ban_has(string $file, string $ip): bool
{
    if (!is_file($file)) return false;
    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    return in_array($ip, array_map('trim', $lines), true);
}

function og_perm_ban_add(string $file, string $ip, string $reason = 'perm_ban', string $rtLog = '', string $uri = '', string $source = 'perm_ban'): void
{
    $list = is_file($file) ? (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []) : [];
    $list = array_map('trim', $list);
    if (!in_array($ip, $list, true)) {
        $list[] = $ip;
        @file_put_contents($file, implode("\n", $list) . "\n", LOCK_EX);
    }
    if ($rtLog !== '') {
        og_ban_reason_log($rtLog, $ip, $uri, 'perm_ban', $reason, $source);
    }
}

function og_remote_gate_candidate(string $method, string $uri): bool
{
    if ($method !== 'GET') return false;
    $path = parse_url($uri, PHP_URL_PATH);
    if ($path === false || $path === null || $path === '') $path = '/';
    if (strpos($path, '/_og_') === 0) return false;
    if (preg_match('/\.(css|js|mjs|png|jpg|jpeg|gif|svg|ico|woff2?|ttf|eot|mp4|webm|webp|pdf|map|json|xml|txt|bmp)(\?.*)?$/i', $path)) {
        return false;
    }
    $dest = strtolower($_SERVER['HTTP_SEC_FETCH_DEST'] ?? '');
    if ($dest !== '' && !in_array($dest, ['document', 'empty'], true)) return false;
    return true;
}

function og_is_browser_document_request(string $method, string $uri, string $ua): bool
{
    if (!in_array($method, ['GET', 'HEAD'], true)) {
        return false;
    }
    $path = parse_url($uri, PHP_URL_PATH);
    if ($path === false || $path === null || $path === '') {
        $path = '/';
    }
    $path_l = strtolower($path);
    if (preg_match('/\.(css|js|mjs|png|jpg|jpeg|gif|svg|ico|woff2?|ttf|eot|mp4|webm|webp|pdf|map|json|xml|txt|bmp|zip|rar|7z|gz|tar)(\?.*)?$/i', $path_l)) {
        return false;
    }
    if ($path_l === '/_site' || og_starts_with($path_l, '/_site/') || $path_l === '/_og' || og_starts_with($path_l, '/_og_') || $path_l === '/api' || og_starts_with($path_l, '/api/')) {
        return false;
    }
    if (in_array($path_l, ['/_og_trap', '/_og_mirror_probe', '/.well-known/og-trap', '/.well-known/og-mirror-probe', '/download-site', '/mirror-site'], true)
        || og_starts_with($path_l, '/.well-known/og-trap/')
        || og_starts_with($path_l, '/.well-known/og-mirror-probe/')
        || og_starts_with($path_l, '/download-site/')
        || og_starts_with($path_l, '/mirror-site/')) {
        return false;
    }

    $ua_l = strtolower($ua);
    if ($ua_l === '' || preg_match('/curl|wget|python|scrapy|httpx|aiohttp|okhttp|bot|spider|crawler|headless|phantom|selenium|webdriver|playwright|puppeteer|java\/|go-http|requests|urllib|httpclient|libwww|axios/i', $ua_l)) {
        return false;
    }
    if (!preg_match('/mozilla|chrome|chromium|safari|firefox|edg|opr|crios|fxios/i', $ua)) {
        return false;
    }

    $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
    $htmlAccept = $accept !== '' && (
        strpos($accept, 'text/html') !== false
        || strpos($accept, 'application/xhtml+xml') !== false
        || (strpos($accept, 'application/xml') !== false && strpos($accept, '*/*') !== false)
    );
    if (!$htmlAccept) {
        return false;
    }

    $dest = strtolower((string)($_SERVER['HTTP_SEC_FETCH_DEST'] ?? ''));
    if ($dest !== '' && !in_array($dest, ['document', 'empty'], true)) {
        return false;
    }
    $mode = strtolower((string)($_SERVER['HTTP_SEC_FETCH_MODE'] ?? ''));
    if ($mode !== '' && !in_array($mode, ['navigate', 'nested-navigate'], true)) {
        return false;
    }
    return true;
}

function og_copy_family_reject_html(int $code = 403): void
{
    http_response_code($code);
    header('Cache-Control: no-store, no-cache');
    header('X-Robots-Tag: noindex');
    header('X-Og-Copy-Rejected: 1');
    header('Content-Type: text/html; charset=utf-8');
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') {
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title></title>'
            . '<style>html,body{margin:0!important;background:#fff!important}</style></head><body></body></html>';
    }
    exit;
}

function og_blank_deny_response(bool $copyRejected = false): void
{
    http_response_code(200);
    header('Cache-Control: no-store, no-cache');
    header('X-Robots-Tag: noindex');
    if ($copyRejected) {
        header('X-Og-Copy-Rejected: 1');
    }
    header('Content-Type: text/html; charset=utf-8');
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') {
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title> </title><style>html,body{margin:0;background:#fff}</style></head><body></body></html>';
    }
    exit;
}

function og_live_family_payload(array $C, string $host): array
{
    if (og_request_is_origin($C, $host)) {
        return ['family' => 'accepted'];
    }

    return ['family' => 'denied', 'copy_rejected' => true];
}

function og_remote_gate_encrypt(array $plain, string $secret): ?array
{
    if (!function_exists('openssl_encrypt')) return null;
    $json = json_encode($plain, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) return null;
    $key = hash('sha256', $secret, true);
    $iv  = random_bytes(12);
    $tag = '';
    $aad = 'OfferGuardRemoteGateV1';
    $ct  = openssl_encrypt($json, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, $aad, 16);
    if ($ct === false) return null;
    return [
        'v'    => 1,
        'alg'  => 'A256GCM',
        'iv'   => base64_encode($iv),
        'tag'  => base64_encode($tag),
        'data' => base64_encode($ct),
    ];
}

function og_remote_gate_decrypt(array $box, string $secret): ?array
{
    if (($box['alg'] ?? '') !== 'A256GCM' || !function_exists('openssl_decrypt')) return null;
    $iv  = base64_decode((string)($box['iv'] ?? ''), true);
    $tag = base64_decode((string)($box['tag'] ?? ''), true);
    $ct  = base64_decode((string)($box['data'] ?? ''), true);
    if ($iv === false || $tag === false || $ct === false || strlen($iv) !== 12) return null;
    $key = hash('sha256', $secret, true);
    $raw = openssl_decrypt($ct, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, 'OfferGuardRemoteGateV1');
    if ($raw === false) return null;
    $json = json_decode($raw, true);
    return is_array($json) ? $json : null;
}

function og_remote_gate_call(array $C, string $ip, string $ua, string $uri, string $method, array $d, int $now): array
{
    $url    = trim((string)($C['remote_gate_url'] ?? ''));
    $secret = (string)($C['remote_gate_secret'] ?? '');
    if ($url === '' || $secret === '') return ['ok' => false, 'error' => 'not_configured'];

    $plain = [
        'v'        => 1,
        'event'    => 'gate',
        'ts'       => $now,
        'nonce'    => bin2hex(random_bytes(12)),
        'host'     => $_SERVER['HTTP_HOST'] ?? '',
        'method'   => $method,
        'uri'      => substr($uri, 0, 1024),
        'ip_hash'  => hash_hmac('sha256', $ip, $secret),
        'ua_hash'  => hash_hmac('sha256', $ua, $secret),
        'state'    => [
            'suspect'     => (int)($d['suspect'] ?? 0),
            'human_score' => (float)($d['human_score'] ?? 0),
            'bait_hits'   => (int)($d['preflight_bait_hits'] ?? 0),
            'strikes'     => (int)($d['strikes_total'] ?? 0),
        ],
    ];
    if (!empty($C['remote_gate_send_ip'])) {
        $plain['ip'] = $ip;
    }
    $box = og_remote_gate_encrypt($plain, $secret);
    if ($box === null) return ['ok' => false, 'error' => 'crypto_unavailable'];

    $respBox = og_http_post_json($url, $box, $secret, (int)($C['remote_gate_timeout'] ?? 2));
    if ($respBox === null) return ['ok' => false, 'error' => 'no_response'];

    $resp = og_remote_gate_decrypt($respBox, $secret);
    if ($resp === null) return ['ok' => false, 'error' => 'bad_response'];

    return [
        'ok'     => true,
        'allow'  => !empty($resp['allow']),
        'ttl'    => max(30, min(86400, (int)($resp['ttl'] ?? ($C['remote_gate_cache_ttl'] ?? 300)))),
        'reason' => preg_replace('/[^a-zA-Z0-9_\-:.]/', '', (string)($resp['reason'] ?? 'remote_gate')),
        'perm'   => !empty($resp['perm']),
    ];
}

function og_b64url_decode(string $s): ?string
{
    $s = strtr($s, '-_', '+/');
    $pad = strlen($s) % 4;
    if ($pad) $s .= str_repeat('=', 4 - $pad);
    $raw = base64_decode($s, true);
    return $raw === false ? null : $raw;
}

function og_b64url_encode(string $s): string
{
    return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
}

function og_live_token_pack(array $C, array $payload): ?string
{
    if (!function_exists('openssl_encrypt')) return null;
    $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) return null;
    $key = hash('sha256', (string)($C['cookie_secret'] ?? '') . '|live-token-v2', true);
    $iv  = random_bytes(12);
    $tag = '';
    $ct = openssl_encrypt($json, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, 'OfferGuardLiveTokenV2', 16);
    if ($ct === false) return null;
    return og_b64url_encode(json_encode([
        'v' => 2,
        'alg' => 'A256GCM',
        'iv' => og_b64url_encode($iv),
        'tag' => og_b64url_encode($tag),
        'ct' => og_b64url_encode($ct),
    ], JSON_UNESCAPED_SLASHES));
}

function og_live_token_unpack(array $C, string $token): ?array
{
    $boxRaw = og_b64url_decode($token);
    $box = $boxRaw !== null ? json_decode($boxRaw, true) : null;
    if (!is_array($box) || ($box['alg'] ?? '') !== 'A256GCM' || !function_exists('openssl_decrypt')) return null;
    $iv  = og_b64url_decode((string)($box['iv'] ?? ''));
    $tag = og_b64url_decode((string)($box['tag'] ?? ''));
    $ct  = og_b64url_decode((string)($box['ct'] ?? ''));
    if ($iv === null || $tag === null || $ct === null || strlen($iv) !== 12) return null;
    $key = hash('sha256', (string)($C['cookie_secret'] ?? '') . '|live-token-v2', true);
    $plain = openssl_decrypt($ct, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, 'OfferGuardLiveTokenV2');
    if ($plain === false) return null;
    $payload = json_decode($plain, true);
    return is_array($payload) ? $payload : null;
}

function og_live_token_expected(array $C, string $ip, string $ua, string $host, string $origin, string $pageUri, string $challenge, string $fp, int $bucket): string
{
    $secret = (string)($C['cookie_secret'] ?? '');
    $uaHash = substr(hash('sha256', $ua), 0, 16);
    $scope = strtolower($host) . '|' . strtolower($origin) . '|' . $pageUri . '|' . $challenge . '|' . substr(hash('sha256', $fp), 0, 16);
    return substr(hash_hmac('sha256', $ip . '|' . $uaHash . '|' . $scope . '|live|' . $bucket, $secret), 0, 40);
}

function og_webhook_mode(array $C): string
{
    $m = strtolower(trim((string)($C['og_webhook_mode'] ?? 'notify')));

    return in_array($m, ['notify', 'authoritative'], true) ? $m : 'notify';
}

function og_webhook_is_authoritative(array $C): bool
{
    return og_webhook_mode($C) === 'authoritative';
}

/** True when request host does not match canonical_host (mirror/copy context). */
function og_webhook_request_is_copy(array $C, string $host): bool
{
    $canon = og_canonical_host_cfg($C);

    return $canon !== '' && !og_host_matches_canonical($host, $canon);
}

/** True when HTTP host matches canonical_host (origin landing, not mirror/file copy). */
function og_request_is_origin(array $C, ?string $host = null): bool
{
    $canon = og_canonical_host_cfg($C);
    if ($canon === '') {
        return true;
    }
    if ($host === null || $host === '') {
        $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    }
    if ($host !== '' && og_host_matches_canonical($host, $canon)) {
        return true;
    }
    // CDN / edge alias: Host may differ while Origin/Referer still point at canonical.
    foreach (['HTTP_ORIGIN', 'HTTP_REFERER'] as $hdr) {
        if (empty($_SERVER[$hdr])) {
            continue;
        }
        $refHost = (string)(parse_url((string)$_SERVER[$hdr], PHP_URL_HOST) ?: '');
        if ($refHost !== '' && og_host_matches_canonical($refHost, $canon)) {
            return true;
        }
    }

    return false;
}

/** Origin-safe: local mint when webhook unreachable unless og_webhook_strict_mint. */
function og_webhook_mint_allows_local_fallback(array $C): bool
{
    if (array_key_exists('og_webhook_strict_mint', $C)) {
        return empty($C['og_webhook_strict_mint']);
    }

    return !empty($C['og_webhook_fail_open']);
}

/** @return ?bool null = no cache entry */
function og_webhook_validate_cache_get(string $token): ?bool
{
    static $cache = [];
    $k = hash('sha256', $token);
    if (!isset($cache[$k])) {
        return null;
    }
    [$ok, $exp] = $cache[$k];
    if (time() > $exp) {
        unset($cache[$k]);

        return null;
    }

    return $ok;
}

function og_webhook_validate_cache_set(string $token, bool $ok): void
{
    static $cache = [];
    $cache[hash('sha256', $token)] = [$ok, time() + 5];
}

function og_webhook_resolve_url(array $C, string $event): string
{
    $event = preg_replace('/[^a-z0-9_]/', '', strtolower($event)) ?: 'unknown';
    if ($event === 'validate') {
        $v = trim((string)($C['og_webhook_validate_url'] ?? ''));
        if ($v !== '') {
            return $v;
        }
    }

    return trim((string)($C['og_webhook_url'] ?? ''));
}

/**
 * POST JSON webhook. notify = fire-and-forget; authoritative + mint_sync/validate = wait for JSON body.
 *
 * @param array{event?:string,token?:string,ip?:string,ts?:int,sid?:string,host?:string} $payload
 * @return ?array{ok?:bool,token?:string,exp?:int,ban?:bool,reason?:string}
 */
function og_webhook_call(array $C, array $payload, bool $waitResponse = false): ?array
{
    $event = preg_replace('/[^a-z0-9_]/', '', strtolower((string)($payload['event'] ?? ''))) ?: 'unknown';
    $url = og_webhook_resolve_url($C, $event);
    if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
        return null;
    }
    $bodyArr = [
        'event' => $event,
        'token' => substr((string)($payload['token'] ?? ''), 0, 96),
        'ip'    => (string)($payload['ip'] ?? ''),
        'ts'    => (int)($payload['ts'] ?? time()),
    ];
    if (!empty($payload['sid'])) {
        $bodyArr['sid'] = substr((string)$payload['sid'], 0, 64);
    }
    if (!empty($payload['host'])) {
        $bodyArr['host'] = substr((string)$payload['host'], 0, 253);
    }
    $body = json_encode($bodyArr, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($body === false) {
        return null;
    }
    $timeout = max(1, min(8, (int)($C['og_webhook_timeout'] ?? 2)));
    $headers = "Content-Type: application/json\r\nAccept: application/json\r\n";
    $secret = trim((string)($C['og_webhook_secret'] ?? ''));
    if ($secret !== '') {
        $headers .= 'X-Og-Webhook-Secret: ' . $secret . "\r\n";
    }
    $mode = og_webhook_mode($C);
    $sync = $waitResponse;
    if (!$sync && $mode === 'authoritative') {
        if (in_array($event, ['mint', 'rotate'], true) && !empty($C['og_webhook_mint_sync'])) {
            $sync = true;
        } elseif ($event === 'validate') {
            $sync = true;
        }
    }
    $ctx = stream_context_create(['http' => [
        'method'          => 'POST',
        'timeout'         => $timeout,
        'ignore_errors'   => true,
        'follow_location' => false,
        'header'          => $headers,
        'content'         => $body,
    ]]);
    $raw = @file_get_contents($url, false, $ctx);
    if (!$sync) {
        return null;
    }
    if ($raw === false || $raw === '') {
        return null;
    }
    $j = json_decode($raw, true);

    return is_array($j) ? $j : null;
}

/** Fire-and-forget notify (backward compatible). */
function og_webhook_live_event(array $C, string $event, string $token, string $ip, array $extra = []): void
{
    og_webhook_call($C, array_merge([
        'event' => $event,
        'token' => $token,
        'ip'    => $ip,
        'ts'    => time(),
    ], $extra), false);
}

/**
 * @param array{pub:string,k:string,exp:int} $minted
 * @return ?array{pub:string,k:string,exp:int}
 */
function og_webhook_apply_mint_response(array &$d, array $C, array $minted, string $sidHash, string $ip, int $now, ?array $wh): ?array
{
    if ($wh === null) {
        return null;
    }
    if (empty($wh['ok']) || !empty($wh['ban'])) {
        unset($d['live_pub_tokens'][$minted['k']]);

        return null;
    }
    $pub = $minted['pub'];
    $exp = $minted['exp'];
    $newTok = trim((string)($wh['token'] ?? ''));
    if ($newTok !== '' && og_live_pub_well_formed($newTok) && $newTok !== $pub) {
        unset($d['live_pub_tokens'][$minted['k']]);
        $pubK = hash_hmac('sha256', $newTok, (string)($C['cookie_secret'] ?? ''));
        $newExp = (int)($wh['exp'] ?? 0);
        if ($newExp <= $now) {
            $newExp = $now + og_live_pub_ttl_secs($C);
        }
        $d['live_pub_tokens'][$pubK] = [
            'exp'  => $newExp,
            'iat'  => $now,
            'sidh' => $sidHash,
            'ip_h' => hash_hmac('sha256', $ip, (string)($C['cookie_secret'] ?? '')),
            'wh'   => 1,
        ];

        return ['pub' => $newTok, 'k' => $pubK, 'exp' => $newExp];
    }
    if (!empty($wh['exp']) && (int)$wh['exp'] > $now) {
        $exp = (int)$wh['exp'];
        if (isset($d['live_pub_tokens'][$minted['k']]) && is_array($d['live_pub_tokens'][$minted['k']])) {
            $d['live_pub_tokens'][$minted['k']]['exp'] = $exp;
            $d['live_pub_tokens'][$minted['k']]['wh'] = 1;
        }

        return ['pub' => $pub, 'k' => $minted['k'], 'exp' => $exp];
    }
    if (isset($d['live_pub_tokens'][$minted['k']]) && is_array($d['live_pub_tokens'][$minted['k']])) {
        $d['live_pub_tokens'][$minted['k']]['wh'] = 1;
    }

    return ['pub' => $pub, 'k' => $minted['k'], 'exp' => $exp];
}

/**
 * Mint/rotate: authoritative waits for webhook; notify fires async POST.
 *
 * @param array{pub:string,k:string,exp:int} $minted
 * @return ?array{pub:string,k:string,exp:int}
 */
function og_webhook_mint_sync(array &$d, array $C, array $minted, string $sidHash, string $ip, int $now, string $host, string $sid, string $event = 'mint'): ?array
{
    $payload = [
        'event' => $event,
        'token' => $minted['pub'],
        'ip'    => $ip,
        'ts'    => $now,
        'host'  => $host,
    ];
    if ($sid !== '') {
        $payload['sid'] = $sid;
    }
    if (!og_webhook_is_authoritative($C) || empty($C['og_webhook_mint_sync'])) {
        og_webhook_call($C, $payload, false);

        return $minted;
    }
    $wh = og_webhook_call($C, $payload, true);
    if ($wh === null) {
        if ($event === 'rotate') {
            unset($d['live_pub_tokens'][$minted['k']]);
            @error_log('[OfferGuard] webhook rotate unreachable — keeping current pub (origin-safe)');

            return null;
        }
        if (og_webhook_mint_allows_local_fallback($C)) {
            @error_log('[OfferGuard] webhook ' . $event . ' unreachable — local mint fallback (origin-safe)');

            return $minted;
        }
        unset($d['live_pub_tokens'][$minted['k']]);

        return null;
    }

    return og_webhook_apply_mint_response($d, $C, $minted, $sidHash, $ip, $now, $wh);
}

/**
 * Authoritative validate; null = use local only (notify).
 * $beatPath: origin heartbeat — webhook timeout always fail-open; explicit deny does not revoke pub.
 */
function og_webhook_validate_sync(array $C, string $token, string $ip, string $host, string $sid = '', bool $beatPath = false): ?bool
{
    if (!og_webhook_is_authoritative($C)) {
        return null;
    }
    $isCopy = og_webhook_request_is_copy($C, $host);
    $cached = og_webhook_validate_cache_get($token);
    if ($cached !== null) {
        return $cached;
    }
    $payload = ['event' => 'validate', 'token' => $token, 'ip' => $ip, 'ts' => time(), 'host' => $host];
    if ($sid !== '') {
        $payload['sid'] = $sid;
    }
    $wh = og_webhook_call($C, $payload, true);
    if ($wh === null) {
        if ($isCopy) {
            $ok = false;
        } elseif ($beatPath) {
            $ok = true;
        } else {
            $ok = !empty($C['og_webhook_fail_open_validate']);
        }
        og_webhook_validate_cache_set($token, $ok);

        return $ok;
    }
    $ok = !empty($wh['ok']) && empty($wh['ban']);
    if (!$ok && $isCopy) {
        og_webhook_validate_cache_set($token, false);

        return false;
    }
    og_webhook_validate_cache_set($token, $ok);

    return $ok;
}

function og_live_pub_prune(array &$d, int $now): void
{
    $d['live_pub_tokens'] = is_array($d['live_pub_tokens'] ?? null) ? $d['live_pub_tokens'] : [];
    foreach ($d['live_pub_tokens'] as $k => $row) {
        if (!is_array($row) || (int)($row['exp'] ?? 0) < $now) {
            unset($d['live_pub_tokens'][$k]);
        }
    }
    if (count($d['live_pub_tokens']) > 64) {
        $d['live_pub_tokens'] = array_slice($d['live_pub_tokens'], -64, null, true);
    }
}

/** @return ?array row + _k (storage key) */
function og_live_pub_row(array &$d, array $C, string $pubRaw, int $now, string $ip): ?array
{
    og_live_pub_prune($d, $now);
    $pubRaw = trim($pubRaw);
    if ($pubRaw === '' || strlen($pubRaw) > 96 || !preg_match('/^[a-fA-F0-9]{16,128}$/', $pubRaw)) {
        return null;
    }
    $pubK = hash_hmac('sha256', $pubRaw, (string)($C['cookie_secret'] ?? ''));
    $row = $d['live_pub_tokens'][$pubK] ?? null;
    if (!is_array($row) || (int)($row['exp'] ?? 0) < $now) {
        return null;
    }
    $ipH = hash_hmac('sha256', $ip, (string)($C['cookie_secret'] ?? ''));
    if (!isset($row['ip_h']) || !hash_equals((string)$row['ip_h'], $ipH)) {
        return null;
    }
    return $row + ['_k' => $pubK];
}

function og_live_pub_detach_all_for_sid(array &$d, string $sidHash): void
{
    if ($sidHash === '') {
        return;
    }
    $d['live_pub_tokens'] = is_array($d['live_pub_tokens'] ?? null) ? $d['live_pub_tokens'] : [];
    foreach ($d['live_pub_tokens'] as $k => $row) {
        if (is_array($row) && isset($row['sidh']) && hash_equals((string)$row['sidh'], $sidHash)) {
            unset($d['live_pub_tokens'][$k]);
        }
    }
}

function og_live_session_clear(array &$d, string $sidHash): void
{
    if ($sidHash === '') {
        return;
    }
    unset($d['live_sessions'][$sidHash]);
    og_live_hook_detach_sid($d, $sidHash);
    if (!empty($d['live_session_hash']) && hash_equals((string)$d['live_session_hash'], $sidHash)) {
        unset(
            $d['live_session_hash'],
            $d['live_session_exp'],
            $d['live_session_last'],
            $d['live_session_host'],
            $d['live_session_origin'],
            $d['live_session_uri'],
            $d['live_session_challenge'],
            $d['live_session_fp'],
        );
    }
}

/** Live-gate hook scope: one browser tab per host|origin|uri|challenge (per IP state file). */
function og_live_hook_key(string $host, string $origin, string $pageUri, string $challenge): string
{
    return substr(hash('sha256', strtolower($host) . '|' . strtolower($origin) . '|' . $pageUri . '|' . $challenge), 0, 32);
}

function og_live_tab_id_valid(string $tabId): bool
{
    $tabId = trim($tabId);

    return $tabId !== ''
        && strlen($tabId) >= 16
        && strlen($tabId) <= 64
        && (bool)preg_match('/^[a-f0-9-]+$/i', $tabId);
}

function og_live_hook_prune(array &$d, int $now): void
{
    $d['live_hook_tabs'] = is_array($d['live_hook_tabs'] ?? null) ? $d['live_hook_tabs'] : [];
    foreach ($d['live_hook_tabs'] as $k => $row) {
        if (!is_array($row) || (int)($row['exp'] ?? 0) < $now) {
            unset($d['live_hook_tabs'][$k]);
        }
    }
    if (count($d['live_hook_tabs']) > 48) {
        $d['live_hook_tabs'] = array_slice($d['live_hook_tabs'], -48, null, true);
    }
}

function og_live_hook_detach_sid(array &$d, string $sidHash): void
{
    if ($sidHash === '') {
        return;
    }
    og_live_hook_prune($d, time());
    foreach ($d['live_hook_tabs'] as $hk => $row) {
        if (is_array($row) && isset($row['sidh']) && hash_equals((string)$row['sidh'], $sidHash)) {
            unset($d['live_hook_tabs'][$hk]);
        }
    }
}

/** True when another tab already owns this hook (leader row live). */
function og_live_hook_tab_blocked(array $d, array $C, string $hookKey, string $tabId, int $now): bool
{
    if (empty($C['live_hook_one_tab']) || !og_live_tab_id_valid($tabId)) {
        return false;
    }
    $cur = $d['live_hook_tabs'][$hookKey] ?? null;
    if (!is_array($cur) || (int)($cur['exp'] ?? 0) < $now) {
        return false;
    }

    return !hash_equals((string)($cur['tab'] ?? ''), $tabId);
}

/**
 * Claim hook leader for tab on confirm. Returns ok | tab_limit.
 *
 * @return 'ok'|'tab_limit'
 */
function og_live_hook_claim(array &$d, array $C, string $hookKey, string $tabId, string $sidHash, string $pubK, int $now, int $leaderTtl): string
{
    if (empty($C['live_hook_one_tab']) || !og_live_tab_id_valid($tabId)) {
        return 'ok';
    }
    og_live_hook_prune($d, $now);
    $d['live_hook_tabs'] = is_array($d['live_hook_tabs'] ?? null) ? $d['live_hook_tabs'] : [];
    $cur = $d['live_hook_tabs'][$hookKey] ?? null;
    $exp = $now + max(60, $leaderTtl);
    if (!is_array($cur) || (int)($cur['exp'] ?? 0) < $now) {
        $d['live_hook_tabs'][$hookKey] = ['tab' => $tabId, 'sidh' => $sidHash, 'pubk' => $pubK, 'exp' => $exp];

        return 'ok';
    }
    if (hash_equals((string)($cur['tab'] ?? ''), $tabId)
        || ($sidHash !== '' && hash_equals((string)($cur['sidh'] ?? ''), $sidHash))) {
        $d['live_hook_tabs'][$hookKey] = ['tab' => $tabId, 'sidh' => $sidHash, 'pubk' => $pubK, 'exp' => $exp];

        return 'ok';
    }

    return 'tab_limit';
}

function og_live_hook_touch(array &$d, string $hookKey, int $now, int $leaderTtl): void
{
    if (!isset($d['live_hook_tabs'][$hookKey]) || !is_array($d['live_hook_tabs'][$hookKey])) {
        return;
    }
    $d['live_hook_tabs'][$hookKey]['exp'] = $now + max(60, $leaderTtl);
}

function og_live_hook_is_leader(array $d, string $hookKey, string $tabId, string $sidHash, int $now): bool
{
    $cur = $d['live_hook_tabs'][$hookKey] ?? null;
    if (!is_array($cur) || (int)($cur['exp'] ?? 0) < $now) {
        return $tabId === '' && $sidHash === '';
    }
    if ($tabId !== '' && hash_equals((string)($cur['tab'] ?? ''), $tabId)) {
        return true;
    }

    return $sidHash !== '' && hash_equals((string)($cur['sidh'] ?? ''), $sidHash);
}

function og_live_hook_release(array &$d, string $hookKey, string $tabId, string $sidHash): void
{
    $cur = $d['live_hook_tabs'][$hookKey] ?? null;
    if (!is_array($cur)) {
        return;
    }
    if (($tabId !== '' && hash_equals((string)($cur['tab'] ?? ''), $tabId))
        || ($sidHash !== '' && hash_equals((string)($cur['sidh'] ?? ''), $sidHash))) {
        unset($d['live_hook_tabs'][$hookKey]);
    }
}

function og_live_pub_well_formed(string $pubRaw): bool
{
    $pubRaw = trim($pubRaw);
    if ($pubRaw === '' || strlen($pubRaw) > 96) {
        return false;
    }
    return (bool)preg_match('/^[a-fA-F0-9]{16,128}$/', $pubRaw);
}

function og_live_pub_ttl_secs(array $C): int
{
    return max(30, min(600, (int)($C['live_pub_ttl'] ?? 60)));
}

/** @return array{pub:string,k:string,exp:int} */
function og_live_pub_mint(array &$d, array $C, string $sidHash, string $ip, int $now): array
{
    og_live_pub_prune($d, $now);
    $ttl = og_live_pub_ttl_secs($C);
    $livePub = bin2hex(random_bytes(20));
    $pubK = hash_hmac('sha256', $livePub, (string)($C['cookie_secret'] ?? ''));
    $d['live_pub_tokens'][$pubK] = [
        'exp' => $now + $ttl,
        'iat' => $now,
        'sidh' => $sidHash,
        'ip_h' => hash_hmac('sha256', $ip, (string)($C['cookie_secret'] ?? '')),
    ];

    return ['pub' => $livePub, 'k' => $pubK, 'exp' => $now + $ttl];
}

/**
 * Бан по подставному/просроченному live pub. Пустой токен сюда не передаём.
 *
 * @return bool true если выставлен atk_block
 */
function og_try_ban_bad_live_pub(array $C, array &$d, string $ip, string $uri, string $why, string $rtLog, int $now): bool
{
    if (empty($C['og_ban_bad_token'])) {
        return false;
    }
    $banTtl = (int)($C['og_ban_bad_token_ttl'] ?? 0);
    if ($banTtl <= 0) {
        $banTtl = (int)($C['atk_block'] ?? 1209600);
    }
    $perMin = max(0, (int)($C['og_ban_bad_token_subnet_per_min'] ?? 12));
    $subnet = og_ip_prefix($ip, (int)($C['subnet_mask_v4'] ?? 24));
    if ($perMin > 0 && $subnet !== 'na') {
        $rlFile = $C['dir'] . '/bad_live_pub_subnet_rl.json';
        $wkey = (string)(int)floor($now / 60);
        $st = is_file($rlFile) ? (json_decode((string)@file_get_contents($rlFile), true) ?? []) : [];
        if (!is_array($st) || (string)($st['w'] ?? '') !== $wkey) {
            $st = ['w' => $wkey, 'n' => []];
        }
        $st['n'] = is_array($st['n'] ?? null) ? $st['n'] : [];
        if ((int)($st['n'][$subnet] ?? 0) >= $perMin) {
            og_runtime_log($rtLog, $ip, $uri, 'bad_live_pub_subnet_rl_skip:' . og_log_field($why, 120) . '|' . $subnet);

            return false;
        }
    }
    og_mark_block($d, 'atk', $now + $banTtl, 'bad_live_pub:' . $why, 'bad_live_pub', $rtLog, $ip, $uri);
    $d['suspect_reasons'][] = 'bad_live_pub:' . $why;
    if ($perMin > 0 && $subnet !== 'na') {
        $rlFile = $C['dir'] . '/bad_live_pub_subnet_rl.json';
        $wkey = (string)(int)floor($now / 60);
        $st = is_file($rlFile) ? (json_decode((string)@file_get_contents($rlFile), true) ?? []) : [];
        if (!is_array($st) || (string)($st['w'] ?? '') !== $wkey) {
            $st = ['w' => $wkey, 'n' => []];
        }
        $st['n'] = is_array($st['n'] ?? null) ? $st['n'] : [];
        $st['n'][$subnet] = (int)($st['n'][$subnet] ?? 0) + 1;
        @file_put_contents($rlFile, json_encode($st, JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    return true;
}

/** @return bool true = ban allowed; false = reload grace blocks ban for this window */
function og_live_reload_grace_allows_ban(array &$d, array $C, int $now): bool
{
    $perMin = max(1, (int)($C['live_pub_reload_grace_per_min'] ?? 3));
    $window = max(30, (int)($C['live_pub_reload_grace_window'] ?? 60));
    $d['reload_grace_log'] = is_array($d['reload_grace_log'] ?? null) ? $d['reload_grace_log'] : [];
    $cutoff = $now - $window;
    $log = [];
    foreach ($d['reload_grace_log'] as $ts) {
        $t = (int)$ts;
        if ($t > $cutoff) {
            $log[] = $t;
        }
    }
    $d['reload_grace_log'] = $log;
    if (count($log) >= $perMin) {
        return true;
    }
    $d['reload_grace_log'][] = $now;

    return false;
}

/**
 * Ban bad live pub with origin reload grace (copy/mirror — без grace; битый формат — всегда бан).
 *
 * @return bool true если выставлен atk_block
 */
function og_try_ban_bad_live_pub_guarded(
    array $C,
    array &$d,
    string $ip,
    string $uri,
    string $why,
    string $rtLog,
    int $now,
    string $host,
    string $pubRaw = ''
): bool {
    if (!og_request_is_origin($C, $host)) {
        return og_try_ban_bad_live_pub($C, $d, $ip, $uri, $why, $rtLog, $now);
    }
    // First visit / before confirm: stale sessionStorage pub must not atk-ban origin.
    if (empty($d['live_token_confirmed']) && empty($d['live_sessions'])) {
        og_runtime_log($rtLog, $ip, $uri, 'bad_live_pub_origin_cold:' . og_log_field($why, 100));

        return false;
    }
    // Origin reload grace: up to live_pub_reload_grace_per_min per window, then ban.
    if (!og_live_reload_grace_allows_ban($d, $C, $now)) {
        og_runtime_log($rtLog, $ip, $uri, 'bad_live_pub_reload_grace:' . og_log_field($why, 120));

        return false;
    }

    return og_try_ban_bad_live_pub($C, $d, $ip, $uri, $why, $rtLog, $now);
}

/**
 * Стандартное отклонение массива чисел
 */
function og_stddev(array $vals): float
{
    $n = count($vals);
    if ($n < 2) return 999.0;
    $mean = array_sum($vals) / $n;
    $sq   = 0.0;
    foreach ($vals as $v) $sq += ($v - $mean) ** 2;
    return sqrt($sq / $n);
}

/**
 * Красивая HTML страница блокировки
 */
function og_block_page(int $code, string $reason, string $ip, string $challenge_html = ''): string
{
    $id      = strtoupper(substr(md5($ip . $reason . time()), 0, 8));
    $host    = htmlspecialchars($_SERVER['HTTP_HOST'] ?? '', ENT_QUOTES, 'UTF-8');
    $ref_raw = $_SERVER['HTTP_REFERER'] ?? '';
    $ref_url = filter_var($ref_raw, FILTER_VALIDATE_URL);
    $ref     = ($ref_url && preg_match('/^https?:\/\//i', $ref_url))
               ? htmlspecialchars($ref_url, ENT_QUOTES, 'UTF-8') : '';
    $ts      = date('Y-m-d H:i:s');
    $backLink= $ref !== '' ? '<a href="' . $ref . '" class="btn btn-ghost">← Назад</a>' : '';

    if (og_starts_with($reason, 'challenge')) {
        $info = ['CHK', 'Проверка безопасности', 'Пожалуйста, решите задачу ниже, чтобы продолжить.', '#3498db'];
    } elseif (og_starts_with($reason, 'ddos') || og_starts_with($reason, 'rate')) {
        $info = ['429', 'Слишком много запросов', 'Вы превысили допустимое количество запросов. Подождите несколько минут.', '#f39c12'];
    } elseif (og_starts_with($reason, 'sql')) {
        $info = ['SQL', 'Запрос заблокирован', 'В запросе обнаружены недопустимые данные.', '#e74c3c'];
    } elseif (og_starts_with($reason, 'xss')) {
        $info = ['XSS', 'Запрос заблокирован', 'Обнаружены потенциально опасные данные.', '#e74c3c'];
    } elseif (og_starts_with($reason, 'lfi')) {
        $info = ['LFI', 'Запрос заблокирован', 'Попытка несанкционированного доступа к файловой системе.', '#e74c3c'];
    } elseif (og_starts_with($reason, 'tor')) {
        $info = ['TOR', 'Tor не поддерживается', 'Доступ через Tor заблокирован.', '#9b59b6'];
    } elseif (og_starts_with($reason, 'datacenter')) {
        $info = ['DC', 'Доступ запрещён', 'Запросы с адресов дата-центров заблокированы.', '#e74c3c'];
    } elseif (og_starts_with($reason, 'geo')) {
        $info = ['GEO', 'Доступ ограничен', 'Доступ из вашего региона ограничен.', '#e67e22'];
    } elseif (og_starts_with($reason, 'scan')) {
        $info = ['SCAN', 'Сканирование обнаружено', 'Попытка сканирования системы заблокирована.', '#e74c3c'];
    } elseif (og_starts_with($reason, 'perm') || og_starts_with($reason, 'permanently')) {
        $info = ['BAN', 'Доступ заблокирован', 'Ваш IP заблокирован за нарушение правил.', '#e74c3c'];
    } elseif (og_starts_with($reason, 'timing')) {
        $info = ['BOT', 'Обнаружена автоматизация', 'Ваша активность соответствует паттернам бота.', '#e74c3c'];
    } elseif (og_starts_with($reason, 'suspect')) {
        $info = ['RISK', 'Подозрительная активность', 'Слишком много признаков автоматизации. Обратитесь в поддержку.', '#e74c3c'];
    } elseif (og_starts_with($reason, 'preflight')) {
        $info = ['TRAP', 'Доступ запрещён', 'Сработала ловушка для автоматического сбора данных.', '#e74c3c'];
    } elseif (og_starts_with($reason, 'honeypot')) {
        $info = ['TRAP', 'Доступ запрещён', 'Обнаружена автоматизированная активность.', '#e74c3c'];
    } elseif (og_starts_with($reason, 'abuse')) {
        $info = ['ABUSE', 'Высокий риск IP', 'Ваш IP числится в базах вредоносных адресов.', '#e74c3c'];
    } elseif (og_starts_with($reason, 'mirror') || og_starts_with($reason, 'download') || og_starts_with($reason, 'static')) {
        $info = ['DL', 'Скачивание заблокировано', 'Обнаружена попытка массового скачивания сайта.', '#e74c3c'];
    } else {
        $info = ['403', 'Доступ запрещён', 'Ваш запрос заблокирован системой защиты. Обратитесь в поддержку.', '#e74c3c'];
    }

    [$icon, $title, $msg, $color] = $info;

    return <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>{$code} — {$title}</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{--accent:{$color};--bg1:#0d0d1a;--bg2:#141428;--bg3:#1a1a35;--text:#d0d0e0;--muted:#666;--border:rgba(255,255,255,0.07)}
body{min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,sans-serif;background:linear-gradient(135deg,var(--bg1) 0%,var(--bg2) 50%,var(--bg3) 100%);color:var(--text);padding:20px}
.wrap{width:100%;max-width:520px;background:rgba(255,255,255,0.035);border:1px solid var(--border);border-radius:20px;padding:52px 44px 40px;text-align:center;backdrop-filter:blur(20px);box-shadow:0 32px 80px rgba(0,0,0,0.6);animation:fadeIn .4s ease}
@keyframes fadeIn{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:none}}
.ring{width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,0.04);border:2px solid color-mix(in srgb,var(--accent) 40%,transparent);display:flex;align-items:center;justify-content:center;margin:0 auto 28px;font-size:15px;font-weight:800;letter-spacing:.08em;color:color-mix(in srgb,var(--accent) 85%,#fff);font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;box-shadow:0 0 0 8px color-mix(in srgb,var(--accent) 8%,transparent),0 0 32px color-mix(in srgb,var(--accent) 20%,transparent);animation:pulse 2.5s ease-in-out infinite}
@keyframes pulse{0%,100%{box-shadow:0 0 0 8px color-mix(in srgb,var(--accent) 8%,transparent),0 0 32px color-mix(in srgb,var(--accent) 20%,transparent)}50%{box-shadow:0 0 0 14px color-mix(in srgb,var(--accent) 4%,transparent),0 0 48px color-mix(in srgb,var(--accent) 30%,transparent)}}
.code{font-size:72px;font-weight:900;letter-spacing:-3px;line-height:1;color:var(--accent);margin-bottom:4px;text-shadow:0 0 40px color-mix(in srgb,var(--accent) 40%,transparent)}
h1{font-size:20px;font-weight:600;color:#fff;margin-bottom:14px}
p{font-size:14px;color:#888;line-height:1.7;margin-bottom:28px}
.actions{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-bottom:28px}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 22px;border-radius:10px;font-size:14px;text-decoration:none;cursor:pointer;border:none;transition:all .2s}
.btn-primary{background:color-mix(in srgb,var(--accent) 20%,transparent);border:1px solid color-mix(in srgb,var(--accent) 35%,transparent);color:color-mix(in srgb,var(--accent) 90%,#fff)}
.btn-primary:hover{background:color-mix(in srgb,var(--accent) 30%,transparent)}
.btn-ghost{background:rgba(255,255,255,0.05);border:1px solid var(--border);color:#888}
.btn-ghost:hover{background:rgba(255,255,255,0.1);color:#bbb}
.meta{font-size:11px;color:var(--muted);border-top:1px solid var(--border);padding-top:20px;display:grid;grid-template-columns:1fr 1fr;gap:6px;text-align:left}
.meta-item{display:flex;flex-direction:column;gap:2px}
.meta-label{color:#444;font-size:10px;text-transform:uppercase;letter-spacing:.6px}
.meta-val{color:#666;font-family:monospace}
.challenge-box{background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:12px;padding:20px;margin-bottom:24px;text-align:left}
.challenge-box label{display:block;color:#aaa;font-size:13px;margin-bottom:8px}
.challenge-box input{width:100%;padding:10px 14px;border-radius:8px;border:1px solid var(--border);background:rgba(255,255,255,0.06);color:#fff;font-size:16px}
.challenge-box button{margin-top:12px;width:100%}
</style>
</head>
<body>
<div class="wrap">
  <div class="ring">{$icon}</div>
  <div class="code">{$code}</div>
  <h1>{$title}</h1>
  <p>{$msg}</p>
  {$challenge_html}
  <div class="actions">
    <button class="btn btn-primary" onclick="location.reload()">Попробовать снова</button>
    {$backLink}
    <button class="btn btn-ghost" onclick="history.back()">← Назад</button>
  </div>
  <div class="meta">
    <div class="meta-item"><span class="meta-label">Хост</span><span class="meta-val">{$host}</span></div>
    <div class="meta-item"><span class="meta-label">ID</span><span class="meta-val">{$id}</span></div>
    <div class="meta-item"><span class="meta-label">Время</span><span class="meta-val">{$ts}</span></div>
    <div class="meta-item"><span class="meta-label">Код</span><span class="meta-val">HTTP {$code}</span></div>
  </div>
</div>
</body>
</html>
HTML;
}

/**
 * Нужен ли preflight-слой (только HTML-документ, не ассеты и не служебные endpoint'ы)
 */
function og_preflight_candidate(array $C, string $method, string $uri): bool
{
    if (empty($C['preflight_on']) || $method !== 'GET') {
        return false;
    }
    $path = parse_url($uri, PHP_URL_PATH);
    if ($path === false || $path === null || $path === '') {
        $path = '/';
    }
    if (preg_match('/\.(css|js|mjs|png|jpg|jpeg|gif|svg|ico|woff2?|ttf|eot|mp4|webm|webp|pdf|map|json|xml|txt|bmp)(\?.*)?$/i', $path)) {
        return false;
    }
    if (strpos($path, '/_og_') === 0 || strpos($path, '/_site/') === 0) {
        return false;
    }
    $ac = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');
    if ($ac !== '' && strpos($ac, 'text/html') === false && strpos($ac, '*/*') === false && strpos($ac, 'text/') !== 0) {
        return false;
    }
    $dest = strtolower($_SERVER['HTTP_SEC_FETCH_DEST'] ?? '');
    if ($dest !== '' && !in_array($dest, ['document', 'empty'], true)) {
        return false;
    }
    return true;
}

function og_guard_endpoint(string $query = ''): string
{
    $docRoot = realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $guardDir = realpath(__DIR__);
    if ($docRoot && $guardDir && strpos($guardDir, $docRoot) === 0) {
        $rel = str_replace('\\', '/', substr($guardDir, strlen($docRoot)));
        $rel = '/' . trim($rel, '/');
        if ($rel === '/') $rel = '';
        return $rel . '/bot-protect.php' . $query;
    }
    return '/bot-protect.php' . $query;
}

/**
 * Невидимый mini-honeypot: белый кадр как «пустая загрузка», без UI; POST через ~1–2 кадра rAF
 */
function og_preflight_interstitial(string $returnUri, string $nonce): string
{
    $ret = htmlspecialchars($returnUri, ENT_QUOTES, 'UTF-8');
    $nn  = htmlspecialchars($nonce, ENT_QUOTES, 'UTF-8');
    $act = htmlspecialchars(og_guard_endpoint('?_og_ep=r'), ENT_QUOTES, 'UTF-8');
    return <<<HPHTML
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title> </title>
<style>html,body{margin:0;padding:0;min-height:100%;background:#fff}</style>
<!-- [OfferGuard:preflight] -->
</head>
<body>
<form id="_ogpf" method="post" action="{$act}" autocomplete="off" style="position:absolute;clip:rect(0,0,0,0);width:1px;height:1px;margin:-1px;border:0;padding:0;overflow:hidden;white-space:nowrap">
<input type="hidden" name="_ogpfn" value="{$nn}">
<input type="hidden" name="r" value="{$ret}">
<input type="email" name="_ogpf_email" value="" tabindex="-1" autocomplete="off" aria-hidden="true">
</form>
<noscript><form method="post" action="{$act}" style="margin:0"><input type="hidden" name="_ogpfn" value="{$nn}"><input type="hidden" name="r" value="{$ret}"><input type="email" name="_ogpf_email" style="display:none" tabindex="-1" value=""><input type="submit" value="" style="position:fixed;inset:0;opacity:0;cursor:pointer;border:0;padding:0" title=""></form></noscript>
<div style="position:absolute;clip:rect(0,0,0,0);width:1px;height:1px;overflow:hidden" aria-hidden="true">
<a href="/_site/l" tabindex="-1">.</a>
<span data-src="/_site/m/a.dat?r=2" data-href="/_site/m/ref"></span>
</div>
<script>
(function(){
var f=document.getElementById('_ogpf');
if(!f)return;
function go(){if(f._ogs)return;f._ogs=1;try{f.submit();}catch(e){}}
function arm(){requestAnimationFrame(function(){requestAnimationFrame(go);});}
if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',arm);else arm();
})();
</script>
</body>
</html>
HPHTML;
}

// ══════════════════════════════════════════════════════════════════
//  ИНИЦИАЛИЗАЦИЯ
// ══════════════════════════════════════════════════════════════════
if (!is_dir($C['dir'])) {
    @mkdir($C['dir'], 0700, true);
    @file_put_contents($C['dir'] . '/.htaccess', "<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nOrder deny,allow\nDeny from all\n</IfModule>\n");
    @file_put_contents($C['dir'] . '/index.php', '<?php // silence');
}

// ── РЕАЛЬНЫЙ IP ────────────────────────────────────────────────────
$ip = '0.0.0.0';
foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'] as $h) {
    if (!empty($_SERVER[$h])) {
        $x = trim(explode(',', $_SERVER[$h])[0]);
        if (filter_var($x, FILTER_VALIDATE_IP)) { $ip = $x; break; }
    }
}
$GLOBALS['__og_xlog_ip'] = $ip;

$ua     = $_SERVER['HTTP_USER_AGENT'] ?? '';
$ua_l   = strtolower($ua);
$uri    = $_SERVER['REQUEST_URI'] ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$now    = time();
$iph    = md5($ip);
$ipf    = $C['dir'] . '/' . $iph . '.json';
$d      = is_file($ipf) ? (json_decode(@file_get_contents($ipf), true) ?? []) : [];
$rtLog  = $C['dir'] . '/og_runtime.log';

set_error_handler(static function (int $sev, string $msg, string $file, int $line) use ($rtLog, $ip, $uri): bool {
    if (in_array($sev, [E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR,E_USER_ERROR,E_RECOVERABLE_ERROR,E_WARNING,E_USER_WARNING], true)) {
        og_runtime_log($rtLog, $ip, $uri, 'ERR[' . $sev . '] ' . $msg . ' @' . basename($file) . ':' . $line);
    }
    return false;
});

register_shutdown_function(static function () use ($rtLog, $ip, $uri): void {
    $e = error_get_last();
    if ($e && in_array($e['type'] ?? 0, [E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR], true)) {
        og_runtime_log($rtLog, $ip, $uri, 'FATAL[' . $e['type'] . '] ' . ($e['message'] ?? '') . ' @' . basename((string)($e['file'] ?? '-')) . ':' . (int)($e['line'] ?? 0));
    }
});

// ── UNCONDITIONAL REQUEST LOG ──────────────────────────────────────
// Кожен запит лишає слід у access-log, незалежно від того через який exit
// bot-protect завершиться (whitelist/jsKey/403/200). Без цього при будь-якому
// early exit лог був порожній і користувач думав що bot-protect мертвий.
$og_access_log = $C['dir'] . '/og_access.log';
$og_access_method = $method ?? 'GET';
$og_access_uri    = substr((string)$uri, 0, 200);
$og_access_ip     = (string)$ip;
$og_access_ep     = (string)($_GET['_og_ep'] ?? '-');
$og_access_origin = (string)($_SERVER['HTTP_ORIGIN'] ?? '-');
register_shutdown_function(static function () use ($og_access_log, $og_access_method, $og_access_uri, $og_access_ip, $og_access_ep, $og_access_origin): void {
    $dir = dirname($og_access_log);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    if (!is_file($og_access_log)) {
        @touch($og_access_log);
        @chmod($og_access_log, 0664);
    }
    $code = http_response_code();
    if ($code === false) $code = 200;
    $reasonHdr = '-';
    if (function_exists('headers_list')) {
        foreach (headers_list() as $h) {
            if (stripos($h, 'X-OG-Reason:') === 0) {
                $reasonHdr = trim(substr($h, strlen('X-OG-Reason:')));
                break;
            }
        }
    }
    $line = date('Y-m-d H:i:s') . ' | ' . str_pad((string)$code, 3) . ' | '
        . str_pad($og_access_method, 4) . ' | ' . str_pad($og_access_ip, 15) . ' | '
        . str_pad('ep=' . $og_access_ep, 16) . ' | '
        . 'reason=' . $reasonHdr . ' | '
        . 'origin=' . substr($og_access_origin, 0, 40) . ' | '
        . $og_access_uri;
    $ok = @file_put_contents($og_access_log, $line . "\n", FILE_APPEND | LOCK_EX);
    if ($ok === false && function_exists('og_runtime_log')) {
        og_runtime_log(dirname($og_access_log) . '/og_runtime.log', $og_access_ip, $og_access_uri, 'ACCESS_LOG_WRITE_FAIL');
    }
});

$d['ip'] = $d['ip'] ?? $ip;

$req_path = parse_url($uri, PHP_URL_PATH);
if ($req_path === false || $req_path === null || $req_path === '') {
    $req_path = '/';
}

// Included from index.php (front controller): never run full guard on static / non-HTML.
$og_script_base = strtolower(basename((string)($_SERVER['SCRIPT_FILENAME'] ?? $_SERVER['SCRIPT_NAME'] ?? '')));
if ($og_script_base !== 'bot-protect.php') {
    $og_inc_ext = strtolower((string)pathinfo($req_path, PATHINFO_EXTENSION));
    if ($og_inc_ext !== '' && preg_match('/^(css|js|mjs|png|jpg|jpeg|gif|svg|ico|woff2?|ttf|eot|mp4|webm|webp|pdf|map|json|xml|txt|bmp|zip|rar|7z|gz|tar)$/i', $og_inc_ext)) {
        return;
    }
    if (in_array(strtolower($req_path), ['/favicon.ico', '/robots.txt'], true)) {
        return;
    }
    $og_inc_accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
    if (in_array($method, ['GET', 'HEAD'], true) && $og_inc_accept !== ''
        && strpos($og_inc_accept, 'text/html') === false
        && strpos($og_inc_accept, 'application/xhtml+xml') === false
        && strpos($og_inc_accept, '*/*') === false) {
        return;
    }
}

// Direct hit on bot-protect.php (Apache rewrite): only API/trap paths — never the whole site.
if ($og_script_base === 'bot-protect.php') {
    $og_ep = (string)($_GET['_og_ep'] ?? '');
    $og_live_ep = (string)($C['live_token_endpoint'] ?? '/_site/s');
    $og_is_direct_api = in_array($req_path, [
        '/_site/v', '/_site/r', '/_site/s', '/_site/a', '/_site/x', '/_og_ping', '/_og_pf_ok', '/_og_page_token',
    ], true)
        || $req_path === $og_live_ep
        || preg_match('#^/_site/(l|m|c)(/|$)#', $req_path) === 1
        || preg_match('#^/(_og_trap|_og_mirror_probe|_og_bait)(/|$)#', $req_path) === 1
        || preg_match('#^/(\.well-known/og-trap|\.well-known/og-mirror-probe|download-site|mirror-site)(/|$)#', $req_path) === 1
        || ($req_path === '/bot-protect.php' && in_array($og_ep, ['s', 'v', 'r', 'a', 'x'], true))
        || ($method === 'GET' && !empty($_GET['sse']) && ($req_path === $og_live_ep || $req_path === '/_og_page_token' || ($req_path === '/bot-protect.php' && $og_ep === 's')))
        || ($req_path === '/bot-protect.php' && $method === 'GET' && $og_ep !== '');
    if (!$og_is_direct_api) {
        // Static asset request routed here by a catch-all .htaccess rule.
        // Try to serve from encrypted asset store (decrypt server-side); otherwise 404.
        $og_da_ext = strtolower((string)pathinfo($req_path, PATHINFO_EXTENSION));
        $og_static_exts = ['css','js','mjs','png','jpg','jpeg','gif','svg','ico','woff','woff2','ttf','eot','webp','avif','bmp','map'];
        if ($og_da_ext !== '' && in_array($og_da_ext, $og_static_exts, true)) {
            static $og_ct_map = ['css'=>'text/css','js'=>'application/javascript','mjs'=>'application/javascript',
                'png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','gif'=>'image/gif',
                'svg'=>'image/svg+xml','ico'=>'image/x-icon','woff'=>'font/woff','woff2'=>'font/woff2',
                'ttf'=>'font/ttf','eot'=>'application/vnd.ms-fontobject','webp'=>'image/webp',
                'avif'=>'image/avif','bmp'=>'image/bmp','map'=>'application/json'];
            $og_ct = $og_ct_map[$og_da_ext] ?? 'application/octet-stream';
            // fileId = sha256(strtolower(relPath)) — must match patcher's og_patch_encrypt_assets().
            // req_path is e.g. "/assets/css/main.css"; ltrim strips the leading slash → relPath.
            $og_da_relpath = strtolower(ltrim($req_path, '/'));
            $og_da_fid     = hash('sha256', $og_da_relpath);
            $og_da_enc     = __DIR__ . '/__OG_ASSETS_SUBDIR__/' . $og_da_fid . '.enc';
            // Fallback: try basename-only hash (for root-level assets or legacy patches).
            if (!is_file($og_da_enc)) {
                $og_da_fid_bn  = hash('sha256', strtolower(basename($req_path)));
                $og_da_enc_bn  = __DIR__ . '/__OG_ASSETS_SUBDIR__/' . $og_da_fid_bn . '.enc';
                if (is_file($og_da_enc_bn)) {
                    $og_da_fid = $og_da_fid_bn;
                    $og_da_enc = $og_da_enc_bn;
                }
            }
            if (function_exists('openssl_decrypt') && is_file($og_da_enc) && is_readable($og_da_enc)) {
                $og_da_json = @file_get_contents($og_da_enc);
                $og_da_j    = $og_da_json !== false ? @json_decode($og_da_json, true) : null;
                if (is_array($og_da_j) && isset($og_da_j['c'], $og_da_j['iv'])) {
                    $og_da_pkey = (string)($C['live_payload_key'] ?? '');
                    $og_da_host = strtolower(trim((string)($C['canonical_host'] ?? '')));
                    if ($og_da_pkey !== '' && $og_da_host !== '' && $og_da_pkey !== 'OG_PAYLOAD_KEY_CHANGE_ME') {
                        $og_da_raw   = og_b64url_decode($og_da_pkey);
                        $og_da_kb    = ($og_da_raw !== null && strlen($og_da_raw) === 32) ? $og_da_raw : hash('sha256', $og_da_pkey, true);
                        $og_da_mk    = hash_hmac('sha256', 'OGAssetMasterV1|' . $og_da_host, $og_da_kb, true);
                        $og_da_fk    = hash_hmac('sha256', 'OGAssetFileV1|'   . $og_da_fid,  $og_da_mk, true);
                        $og_da_ctag  = og_b64url_decode((string)$og_da_j['c']);
                        $og_da_iv    = og_b64url_decode((string)$og_da_j['iv']);
                        if ($og_da_ctag !== null && $og_da_iv !== null && strlen($og_da_ctag) > 16) {
                            $og_da_ct  = substr($og_da_ctag, 0, -16);
                            $og_da_tag = substr($og_da_ctag, -16);
                            $og_da_plain = openssl_decrypt($og_da_ct, 'aes-256-gcm', $og_da_fk, OPENSSL_RAW_DATA, $og_da_iv, $og_da_tag);
                            if ($og_da_plain !== false) {
                                // Headers sent only on success — avoids Content-Type:text/css on a 404 body.
                                header('Content-Type: ' . $og_ct);
                                header('Cache-Control: no-store, no-cache');
                                header('X-Robots-Tag: noindex');
                                http_response_code(200);
                                echo $og_da_plain;
                                exit;
                            }
                        }
                    }
                }
            }
            // No encrypted version found or decryption failed — standard 404.
            header('Cache-Control: no-store, no-cache');
            header('X-Robots-Tag: noindex');
            http_response_code(404);
            exit;
        }
        header('Cache-Control: no-store, no-cache');
        header('X-Robots-Tag: noindex');
        http_response_code(404);
        exit;
    }
}

function og_canonical_host_cfg(array $C): string
{
    $h = strtolower(trim((string)($C['canonical_host'] ?? '')));
    if ($h === '' || $h === 'og_canonical_host_change_me') {
        return '';
    }

    return $h;
}

/** AES-GCM AAD for host-bound landing ciphertext (v2). */
function og_payload_aad_v2(string $canonicalHostLower, string $challengeNonce = ''): string
{
    return 'OfferGuardHtmlV2|' . strtolower(trim($canonicalHostLower)) . '|' . trim($challengeNonce);
}

/** Derive per-page content key from master live_payload_key + canonical host + challenge nonce. */
function og_derive_content_key_bytes(string $masterKeyB64, string $canonicalHostLower, string $challengeNonce): string
{
    $master = og_payload_key_bytes($masterKeyB64);
    $aad = og_payload_aad_v2($canonicalHostLower, $challengeNonce);

    return hash_hmac('sha256', $aad, $master, true);
}

/** Runtime mirror of og_patch_derive_kfrag_boot — origin-only, sent in confirm so JS can rebuild split K. */
function og_derive_kfrag_boot(string $masterKeyB64, string $canonicalHostLower, string $challengeNonce): string
{
    $master = og_payload_key_bytes($masterKeyB64);

    return hash_hmac('sha256', 'OGKFBOOTv1|' . strtolower(trim($canonicalHostLower)) . '|' . trim($challengeNonce), $master, true);
}

function og_payload_key_bytes(string $keyB64): string
{
    $key = og_b64url_decode($keyB64);
    if ($key === null || $key === '') {
        $key = base64_decode(strtr($keyB64, '-_', '+/'), true);
    }
    if (!is_string($key) || $key === '') {
        return hash('sha256', $keyB64, true);
    }

    return strlen($key) === 32 ? $key : hash('sha256', $keyB64, true);
}

/** Live gate: session payload key only on canonical origin (mirror confirm never receives k). */
function og_live_derive_payload_key_b64(array $C, string $host, string $challenge): ?string
{
    if (!og_request_is_origin($C, $host)) {
        return null;
    }
    $canon = og_canonical_host_cfg($C);
    if ($canon === '') {
        return null;
    }
    $master = trim((string)($C['live_payload_key'] ?? ''));
    // Сентинел собран конкатенацией: write-time str_replace заменяет только цельный
    // литерал (строка конфига), а этот сравнительный — нет. Иначе $master===<реальный ключ>
    // было бы всегда true и confirm вечно отдавал copy_host_denied.
    if ($master === '' || $master === ('OG_PAYLOAD_KEY' . '_CHANGE_ME')) {
        return null;
    }
    if ($challenge === '') {
        return null;
    }

    return og_b64url_encode(og_derive_content_key_bytes($master, $canon, $challenge));
}

/** Asset master key: stable per (live_payload_key, canonical_host). Sent in confirm only to canonical origin. */
function og_live_derive_asset_master_key_b64(array $C, string $host): ?string
{
    if (!og_request_is_origin($C, $host)) {
        return null;
    }
    $canon = og_canonical_host_cfg($C);
    if ($canon === '') {
        return null;
    }
    $master = trim((string)($C['live_payload_key'] ?? ''));
    if ($master === '' || $master === ('OG_PAYLOAD_KEY' . '_CHANGE_ME')) {
        return null;
    }
    $masterBytes = og_payload_key_bytes($master);
    $assetKey = hash_hmac('sha256', 'OGAssetMasterV1|' . $canon, $masterBytes, true);
    return og_b64url_encode($assetKey);
}

/** Strip optional www. for apex equivalence (example.com ↔ www.example.com). */
function og_patch_host_apex(string $host): string
{
    $h = strtolower(trim(preg_replace('/:\d+$/', '', $host) ?? $host));
    if ($h !== '' && str_starts_with($h, 'www.')) {
        return substr($h, 4);
    }

    return $h;
}

function og_host_matches_canonical(string $host, string $canonical): bool
{
    $host = strtolower(trim($host));
    $canonical = strtolower(trim($canonical));
    if ($canonical === '' || $host === '') {
        return true;
    }
    $hostOnly = preg_replace('/:\d+$/', '', $host) ?? $host;
    $canonOnly = preg_replace('/:\d+$/', '', $canonical) ?? $canonical;

    if (hash_equals($canonical, $host) || hash_equals($canonical, $hostOnly)
        || hash_equals($canonOnly, $host) || hash_equals($canonOnly, $hostOnly)) {
        return true;
    }

    $hApex = og_patch_host_apex($hostOnly);
    $cApex = og_patch_host_apex($canonOnly);

    return $hApex !== '' && hash_equals($hApex, $cApex);
}

/** Mirror validate: never family — explicit copy_not_family (no fake mint path). */
function og_live_copy_validate_decoy_response(array $C, int $now): void
{
    $payload = [
        'ok'            => false,
        'valid'         => false,
        'accepted'      => false,
        'blocked'       => true,
        'reason'        => 'copy_not_family',
        'copy_rejected' => true,
        'family'        => 'denied',
        'h'             => (string)($_SERVER['HTTP_HOST'] ?? ''),
    ];
    http_response_code(200);
    header('X-Og-Copy-Rejected: 1');
    echo json_encode(['p' => og_b64url_encode(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))]);
}

/**
 * Mirror/copy live gate: mint/confirm/beat/revoke/issue never succeed; stale pub → instant ban (no grace).
 */
function og_live_copy_gate_deny(
    array $C,
    array &$d,
    string $ip,
    string $uri,
    string $rtLog,
    int $now,
    string $action,
    ?array $j,
    string $ipf
): void {
    // Mirror/copy: any live-gate touch → instant atk (no origin reload grace).
    og_try_ban_bad_live_pub($C, $d, $ip, $uri, 'copy_live_gate:' . $action, $rtLog, $now);
    $pubProbe = is_array($j) ? trim((string)($j['pub'] ?? '')) : '';
    if ($pubProbe === '' && !empty($_SERVER['HTTP_X_OG_TOKEN'])) {
        $pubProbe = trim((string)$_SERVER['HTTP_X_OG_TOKEN']);
    }
    if ($pubProbe !== '' && in_array($action, ['beat', 'confirm', 'revoke'], true)) {
        if (!og_live_pub_well_formed($pubProbe)) {
            og_try_ban_bad_live_pub($C, $d, $ip, $uri, 'copy_' . $action . '_malformed_pub', $rtLog, $now);
        } else {
            og_try_ban_bad_live_pub($C, $d, $ip, $uri, 'copy_' . $action . '_stale_pub', $rtLog, $now);
        }
        og_save($d, $ipf);
    }
    $reason = 'copy_rejected';
    if (!empty($C['live_hook_one_tab']) && in_array($action, ['beat', 'issue', 'confirm'], true)) {
        $reason = 'tab_limit';
    }
    http_response_code(200);
    header('X-Og-Copy-Rejected: 1');
    echo json_encode(['p' => og_b64url_encode(json_encode([
        'ok'            => false,
        'blocked'       => true,
        'reason'        => $reason,
        'copy_rejected' => true,
        'family'        => 'denied',
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))]);
    exit;
}

$og_canonical_host = og_canonical_host_cfg($C);
$og_is_origin = og_request_is_origin($C);
og_xlog('debug', 'req_entry', 'request_in', [
    'method' => $method,
    'path' => $req_path,
    'is_origin' => $og_is_origin,
    'canon' => $og_canonical_host,
    'host' => (string)($_SERVER['HTTP_HOST'] ?? ''),
    'referer' => substr((string)($_SERVER['HTTP_REFERER'] ?? ''), 0, 80),
    'origin' => substr((string)($_SERVER['HTTP_ORIGIN'] ?? ''), 0, 80),
    'sec_fetch_site' => (string)($_SERVER['HTTP_SEC_FETCH_SITE'] ?? ''),
    'ua' => substr($ua, 0, 60),
]);
if ($og_canonical_host !== '' && str_contains($req_path, '__OG_ASSETS_SUBDIR__')) {
    $og_asset_origin = (string)($_SERVER['HTTP_ORIGIN'] ?? '');
    $og_asset_fetch_site = strtolower((string)($_SERVER['HTTP_SEC_FETCH_SITE'] ?? ''));
    if ($og_asset_fetch_site === 'cross-site') {
        if (!headers_sent()) header('X-OG-Reason: asset_cross_site');
        http_response_code(403);
        header('Cache-Control: no-store, no-cache');
        header('X-Robots-Tag: noindex');
        exit;
    }
    if ($og_asset_origin !== '') {
        $og_asset_origin_host = (string)(parse_url($og_asset_origin, PHP_URL_HOST) ?: '');
        $og_asset_origin_port = parse_url($og_asset_origin, PHP_URL_PORT);
        if ($og_asset_origin_host !== '' && $og_asset_origin_port) {
            $og_asset_origin_host .= ':' . $og_asset_origin_port;
        }
        if ($og_asset_origin_host !== '' && !og_host_matches_canonical($og_asset_origin_host, $og_canonical_host)) {
            if (!headers_sent()) header('X-OG-Reason: asset_origin_mismatch');
            http_response_code(403);
            header('Cache-Control: no-store, no-cache');
            header('X-Robots-Tag: noindex');
            exit;
        }
    }
    $og_asset_referer = (string)($_SERVER['HTTP_REFERER'] ?? '');
    if ($og_asset_referer !== '') {
        $og_asset_ref_host = (string)(parse_url($og_asset_referer, PHP_URL_HOST) ?: '');
        $og_asset_ref_port = parse_url($og_asset_referer, PHP_URL_PORT);
        if ($og_asset_ref_host !== '' && $og_asset_ref_port) {
            $og_asset_ref_host .= ':' . $og_asset_ref_port;
        }
        if ($og_asset_ref_host !== '' && !og_host_matches_canonical($og_asset_ref_host, $og_canonical_host)) {
            if (!headers_sent()) header('X-OG-Reason: asset_referer_mismatch');
            http_response_code(403);
            header('Cache-Control: no-store, no-cache');
            header('X-Robots-Tag: noindex');
            exit;
        }
    }
    if (!og_host_matches_canonical((string)($_SERVER['HTTP_HOST'] ?? ''), $og_canonical_host)) {
        if (!headers_sent()) header('X-OG-Reason: asset_host_mismatch');
        http_response_code(403);
        header('Cache-Control: no-store, no-cache');
        header('X-Robots-Tag: noindex');
        exit;
    }
}

$og_req_ext = (string)pathinfo($req_path, PATHINFO_EXTENSION);
$og_is_asset_req = $og_req_ext !== '' && preg_match('/^(css|js|mjs|png|jpg|jpeg|gif|svg|ico|woff2?|ttf|eot|mp4|webm|webp|pdf|map|json|xml|txt|bmp)$/i', $og_req_ext);
$is_static = (bool)$og_is_asset_req;
$og_browser_shell_candidate = og_is_browser_document_request($method, $uri, $ua);
if ($og_canonical_host !== '' && !$og_is_origin && $og_browser_shell_candidate
    && in_array($method, ['GET', 'HEAD'], true)
    && !in_array($req_path, ['/_site/s', '/_og_page_token', '/_site/v', '/_og_ping', '/_site/a'], true)
    && !(basename($req_path) === 'bot-protect.php' && in_array((string)($_GET['_og_ep'] ?? ''), ['s', 'v'], true))) {
    og_copy_family_reject_html(403);
}

// ── БЫСТРЫЙ БЛОКИРОВЩИК ────────────────────────────────────────────
$og_fast_block = static function (int $code = 429) use ($req_path, $method, $og_browser_shell_candidate, $og_is_origin): void {
    $isLiveProbe = $method === 'POST' && (
        in_array($req_path, ['/_site/s', '/_og_page_token'], true)
        || (basename($req_path) === 'bot-protect.php' && (string)($_GET['_og_ep'] ?? '') === 's')
    );
    if ($isLiveProbe) {
        http_response_code(200);
        header('Cache-Control: no-store, no-cache');
        header('X-Robots-Tag: noindex');
        header('Content-Type: application/json; charset=utf-8');
        if (!$og_is_origin) {
            header('X-Og-Copy-Rejected: 1');
        }
        $reason = $code === 429 ? 'rate_limited' : 'ip_banned';
        $deny = ['ok' => false, 'blocked' => true, 'reason' => $reason, 'code' => $code];
        if (!$og_is_origin) {
            $deny['copy_rejected'] = true;
            $deny['family'] = 'denied';
        }
        echo json_encode(['p' => og_b64url_encode(json_encode($deny, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))]);
        exit;
    }
    if ($og_browser_shell_candidate) {
        og_blank_deny_response(!$og_is_origin);
    }
    http_response_code($code);
    header('Cache-Control: no-store, no-cache');
    header('X-Robots-Tag: noindex');
    if (!$og_is_origin) {
        header('X-Og-Copy-Rejected: 1');
    }
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title> </title><style>html,body{margin:0;background:#fff}</style></head><body></body></html>';
    exit;
};

$og_soft_ban_reasons = [
    'no_mouse', 'copy_dump', 'print_attempt', 'save_attempt', 'view_source_attempt',
    'extension_ctx', 'extension_dom', 'live_token_fail',
    'instant_suspect', 'suspect_score_exceeded', 'suspect_score_exceeded_early',
];
$og_is_soft_ban_state = static function (array $state) use ($og_soft_ban_reasons): bool {
    $reasons = array_map('strval', $state['suspect_reasons'] ?? []);
    foreach ($reasons as $reason) {
        foreach ($og_soft_ban_reasons as $soft) {
            if (stripos($reason, $soft) !== false) {
                return true;
            }
        }
    }
    return false;
};
$og_hard_js_flags = array_fill_keys([
    'fake_mouse', 'webdriver_flags', 'cdp_marker', 'cdc_window', 'webdriver_dom',
    'automation_script', 'parser_global', 'parser_dom_marker', 'parser_attr',
    'parser_selector', 'parser_probe', 'html_probe',
], true);
$og_js_flag_penalties = [
    'no_mouse' => 5, 'copy_dump' => 5, 'print_attempt' => 5, 'save_attempt' => 5,
    'view_source_attempt' => 5, 'extension_ctx' => 10, 'extension_dom' => 10,
    'fake_mouse' => 70, 'webdriver_flags' => 70, 'cdp_marker' => 70,
    'cdc_window' => 70, 'webdriver_dom' => 70, 'automation_script' => 70,
    'parser_global' => 80, 'parser_dom_marker' => 80, 'parser_attr' => 80,
    'parser_selector' => 80, 'parser_probe' => 80, 'html_probe' => 80,
];
$og_apply_js_flag = static function (string $flag, array &$state) use ($og_js_flag_penalties, $og_hard_js_flags, $C, $now, $rtLog, $ip, $uri, $og_is_origin): ?array {
    if (!isset($og_js_flag_penalties[$flag])) {
        return null;
    }
    $penalty = (int)$og_js_flag_penalties[$flag];
    $state['suspect'] = min(100, (int)($state['suspect'] ?? 0) + $penalty);
    $state['suspect_reasons'][] = $flag;
    $hard = isset($og_hard_js_flags[$flag]);
    if ($hard && $og_is_origin) {
        $hard = false;
        $penalty = min($penalty, 25);
    }
    if ($hard) {
        og_mark_block($state, 'atk', $now + (int)$C['atk_block'], 'js_flag:' . $flag, 'js_beacon', $rtLog, $ip, $uri);
        $state['last_violation'] = $now;
    } else {
        $state['suspect_last'] = $now;
    }
    return ['hard' => $hard, 'penalty' => $penalty];
};
$og_soft_only_reasons = static function (array $state) use ($og_soft_ban_reasons, $og_hard_js_flags): bool {
    $reasons = array_map('strval', $state['suspect_reasons'] ?? []);
    if (empty($reasons)) {
        return false;
    }
    foreach ($reasons as $reason) {
        $name = strtolower(strtok($reason, ':') ?: $reason);
        if (isset($og_hard_js_flags[$name]) || preg_match('/^(parser_|webdriver_|automation_|cdc_|cdp_|fake_mouse|trap_|preflight_bait|preflight_hp_trip)/i', $name)) {
            return false;
        }
        $soft = false;
        foreach ($og_soft_ban_reasons as $softReason) {
            if (stripos($name, $softReason) !== false) {
                $soft = true;
                break;
            }
        }
        if (!$soft) {
            return false;
        }
    }
    return true;
};

// ── ДЕГРАДАЦИЯ СЧЁТЧИКОВ ───────────────────────────────────────────
if (!empty($d['last_violation']) && ($now - (int)$d['last_violation']) > (int)$C['strike_decay']) {
    $d['rl_strikes']     = 0;
    $d['mirror_strikes'] = 0;
    $d['strikes_total']  = 0;
}
// Деградация suspect score — только если IP ни разу не был замечен в сигналах
if (!empty($d['suspect_last']) && ($now - (int)$d['suspect_last']) > (int)$C['suspect_decay']) {
    // Не сбрасываем если есть накопленные причины подозрения
    if (empty($d['suspect_reasons'])) {
        $d['suspect'] = max(0, ((int)($d['suspect'] ?? 0)) - 20);
    }
}
if ((!empty($d['atk_block']) || !empty($d['rl_block'])) && $og_soft_only_reasons($d)) {
    unset($d['atk_block'], $d['rl_block'], $d['perm_banned']);
    $d['suspect'] = min(40, (int)($d['suspect'] ?? 0));
    og_save($d, $ipf);
}

// ── РАННИЙ WHITELIST ───────────────────────────────────────────────
$og_is_whitelisted = false;
$wl_file_early = $C['dir'] . '/whitelist.txt';
if (is_file($wl_file_early)) {
    $wl_early = file($wl_file_early, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    $og_is_whitelisted = in_array($ip, array_map('trim', $wl_early), true);
}
$isDirectBeacon = $method === 'POST'
    && basename($req_path) === 'bot-protect.php'
    && (string)($_GET['_og_ep'] ?? '') === 'v';
$liveEndpoint = (string)($C['live_token_endpoint'] ?? '/_site/s');
$isDirectLiveToken = $method === 'POST'
    && basename($req_path) === 'bot-protect.php'
    && (string)($_GET['_og_ep'] ?? '') === 's';
// SSE heartbeat (EventSource is GET-only) + origin-gated JS-bundle key (GET).
$isLiveSseGet = $method === 'GET'
    && !empty($_GET['sse'])
    && ($req_path === $liveEndpoint || $req_path === '/_og_page_token'
        || (basename($req_path) === 'bot-protect.php' && (string)($_GET['_og_ep'] ?? '') === 's'));
$isJsKeyGet = $method === 'GET' && (string)($_GET['_og_ep'] ?? '') === 'OG_JSK_EP_CHANGE_ME';
$isAssetGet = $method === 'GET'
    && ($req_path === '/_site/a'
        || (basename($req_path) === 'bot-protect.php' && (string)($_GET['_og_ep'] ?? '') === 'a'));
// Клиентский xlog: JS-гард POST'ит сюда {level, fn, msg, ctx} → пишем как src='cli'.
$isXlogPost = $method === 'POST'
    && ($req_path === '/_site/x'
        || (basename($req_path) === 'bot-protect.php' && (string)($_GET['_og_ep'] ?? '') === 'x'));
if ($isXlogPost && !empty($C['xlog_on'])) {
    // Простой rate-limit для клиента: счётчик в state-файле IP.
    $xlogIpf = $C['dir'] . '/' . md5($ip) . '.json';
    $xlogState = is_file($xlogIpf) ? (json_decode((string)@file_get_contents($xlogIpf), true) ?: []) : [];
    $bucket = (int)floor(time() / 60);
    if (($xlogState['xlog_bucket'] ?? 0) !== $bucket) {
        $xlogState['xlog_bucket'] = $bucket;
        $xlogState['xlog_count'] = 0;
    }
    $cap = max(1, (int)($C['xlog_client_rate_min'] ?? 60));
    if ((int)($xlogState['xlog_count'] ?? 0) < $cap) {
        $xlogRaw = (string)file_get_contents('php://input');
        $xlogJ = $xlogRaw !== '' ? json_decode($xlogRaw, true) : null;
        if (is_array($xlogJ)) {
            $clvl = strtolower((string)($xlogJ['level'] ?? 'info'));
            if (!in_array($clvl, ['debug', 'info', 'warn', 'error', 'fatal'], true)) $clvl = 'info';
            $cfn  = substr((string)($xlogJ['fn']  ?? '-'), 0, 60);
            $cmsg = substr((string)($xlogJ['msg'] ?? ''),  0, 500);
            $cctx = is_array($xlogJ['ctx'] ?? null) ? $xlogJ['ctx'] : [];
            $xlogState['xlog_count'] = (int)($xlogState['xlog_count'] ?? 0) + 1;
            $xlogState['ip'] = $ip;
            @file_put_contents($xlogIpf, json_encode($xlogState), LOCK_EX);
            og_xlog($clvl, $cfn, $cmsg, $cctx, 'cli');
        }
    }
    http_response_code(204);
    header('Cache-Control: no-store, no-cache');
    exit;
}

if ($og_is_whitelisted) {
    if ((in_array($req_path, ['/_site/v', '/_og_ping'], true) || $isDirectBeacon) && $method === 'POST') {
        http_response_code(204);
        exit;
    }
    // КРИТИЧНО: jsKeyGet МУСИТЬ повертати ключ JS-бандла навіть для whitelisted IP.
    // Інакше browser отримує empty 200 → не може розпакувати JS guard → blank page.
    if ($isJsKeyGet) {
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Content-Type: application/json; charset=utf-8');
        $hostWl = (string)($_SERVER['HTTP_HOST'] ?? '');
        if (!og_request_is_origin($C, $hostWl)) {
            if (!headers_sent()) header('X-OG-Reason: jsk_not_origin');
            http_response_code(403);
            echo json_encode(['ok' => false, 'r' => 'not_origin']);
            exit;
        }
        $__jksSecWl = '__OG_SECRET_PATH__';
        if ($__jksSecWl === '' || !@is_readable($__jksSecWl)
            || strlen((string)@file_get_contents($__jksSecWl)) < 32) {
            if (!headers_sent()) header('X-OG-Reason: jsk_no_secret');
            http_response_code(403);
            echo json_encode(['ok' => false, 'r' => 'no_secret']);
            exit;
        }
        $lcWl = og_canonical_host_cfg($C);
        $masterWl = trim((string)($C['live_payload_key'] ?? ''));
        if ($lcWl === '' || strlen($masterWl) < 16) {
            if (!headers_sent()) header('X-OG-Reason: jsk_no_key');
            http_response_code(404);
            echo json_encode(['ok' => false, 'r' => 'no_key']);
            exit;
        }
        $jsKeyWl = hash_hmac('sha256', 'OfferGuardJsBundleV1|' . $lcWl, og_payload_key_bytes($masterWl), true);
        echo json_encode(['ok' => true, 'jk' => og_b64url_encode($jsKeyWl)]);
        exit;
    }
    if ($method === 'POST' && ($req_path === $liveEndpoint || $req_path === '/_og_page_token' || $isDirectLiveToken)) {
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('X-Robots-Tag: noindex, nofollow');
        header('Content-Type: application/json; charset=utf-8');

        $rawInput = (string)file_get_contents('php://input');
        $body = $rawInput !== '' ? json_decode($rawInput, true) : null;
        if (!is_array($body) && isset($_POST['p'])) {
            $body = ['p' => (string)$_POST['p']];
        }
        $packet = is_array($body) ? (string)($body['p'] ?? '') : '';
        $plain = $packet !== '' ? og_b64url_decode($packet) : null;
        $j = $plain !== null ? json_decode($plain, true) : null;
        $action = is_array($j) ? (string)($j['a'] ?? 'issue') : 'issue';
        if (!in_array($action, ['issue', 'confirm', 'beat', 'revoke', 'validate'], true)) {
            $action = 'issue';
        }

        $host = is_array($j) ? (string)($j['h'] ?? ($_SERVER['HTTP_HOST'] ?? '')) : (string)($_SERVER['HTTP_HOST'] ?? '');
        $origin = is_array($j) ? (string)($j['o'] ?? '') : '';
        $pageUri = is_array($j) ? (string)($j['u'] ?? '/') : '/';
        $challenge = is_array($j) ? (string)($j['r'] ?? '') : '';
        $ttlBeat = max(15, min(120, (int)($C['live_session_ttl'] ?? 25)));
        og_xlog('debug', 'wl_live_token', 'action_entry', ['action' => $action, 'host' => $host, 'origin' => $origin, 'uri' => $pageUri, 'chal' => substr($challenge, 0, 12), 'wl' => true]);
        $resp = ['ok' => true, 'h' => $host, 'o' => $origin, 'u' => $pageUri, 'r' => $challenge, 'wl' => true];
        if ($action === 'validate') {
            $resp += ['valid' => true];
        } elseif ($action === 'revoke') {
            $resp += ['revoked' => true];
        } elseif ($action === 'beat') {
            $resp += ['alive' => true, 'exp' => $now + $ttlBeat];
            // Mirror split-key refresh so JS _ogKfExp doesn't expire → _ogHardWipe() → white screen.
            if (!empty($C['live_key_split'])) {
                $kfTtlWlBeat = max(3, min(60, (int)($C['live_kfrag_ttl'] ?? 8)));
                $resp['split'] = 1;
                $resp['kf']    = og_b64url_encode(random_bytes(32));
                $resp['kfb']   = (int)floor($now / $kfTtlWlBeat);
                $resp['kft']   = $kfTtlWlBeat;
            }
        } elseif ($action === 'confirm') {
            $wlKey = og_live_derive_payload_key_b64($C, $host, $challenge);
            $wlAssetKey = og_live_derive_asset_master_key_b64($C, $host);
            og_xlog($wlKey === null ? 'error' : 'info', 'wl_confirm', 'derive_keys', ['has_key' => $wlKey !== null, 'has_ak' => $wlAssetKey !== null, 'split' => !empty($C['live_key_split']), 'reason' => $wlKey === null ? 'derive_payload_key_returned_null (likely og_request_is_origin=false; check Referer/Origin headers)' : 'ok']);
            $resp += [
                'c' => true,
                'k' => $wlKey !== null ? $wlKey : '',
                's' => 'wl-' . substr(hash_hmac('sha256', $ip . '|' . $ua . '|' . $now, (string)$C['cookie_secret']), 0, 24),
                'hb' => 8,
                'sexp' => $now + $ttlBeat,
            ];
            if ($wlAssetKey !== null) {
                $resp['ak'] = $wlAssetKey;
            }
            // Whitelist confirm must mirror split-key mode if server-side encryption used split derivation,
            // otherwise JS imports the wrong key shape and AES-GCM decryption fails (empty og-content).
            if (!empty($C['live_key_split']) && $wlKey !== null) {
                $canonKfWl = og_canonical_host_cfg($C);
                $masterKfWl = trim((string)($C['live_payload_key'] ?? ''));
                if ($canonKfWl !== '' && strlen($masterKfWl) >= 16 && $challenge !== '') {
                    $kfTtlWl = max(3, min(60, (int)($C['live_kfrag_ttl'] ?? 8)));
                    $resp['split'] = 1;
                    $resp['kf0'] = og_b64url_encode(og_derive_kfrag_boot($masterKfWl, $canonKfWl, $challenge));
                    $resp['kfb'] = 0;
                    $resp['kft'] = $kfTtlWl;
                    $resp['hb'] = min(8, $kfTtlWl);
                }
            }
        } else {
            $resp += [
                'phase' => 'issue',
                't' => 'wl-' . substr(hash_hmac('sha256', $ip . '|' . $ua . '|' . $host . '|' . $pageUri . '|' . $challenge, (string)$C['cookie_secret']), 0, 32),
                'exp' => $now + max(30, min(600, (int)($C['live_token_ttl'] ?? 120))),
            ];
        }
        echo json_encode(['p' => og_b64url_encode(json_encode($resp, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))]);
        exit;
    }
    // Whitelisted IPs still need encrypted assets — token check skipped, origin still required.
    if ($isAssetGet) {
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('X-Robots-Tag: noindex');
        header('Content-Type: application/json; charset=utf-8');
        $assetHostWl = (string)($_SERVER['HTTP_HOST'] ?? '');
        if ($og_canonical_host === '' || !og_request_is_origin($C, $assetHostWl)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'r' => 'not_origin']);
            exit;
        }
        $assetFHashWl = preg_replace('/[^a-f0-9]/', '', strtolower((string)($_GET['f'] ?? '')));
        if (strlen($assetFHashWl) < 8 || strlen($assetFHashWl) > 64) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'r' => 'bad_hash']);
            exit;
        }
        $assetEncFileWl = __DIR__ . '/__OG_ASSETS_SUBDIR__/' . $assetFHashWl . '.enc';
        if (!is_file($assetEncFileWl) || !@is_readable($assetEncFileWl)) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'r' => 'not_found']);
            exit;
        }
        $assetEncJsonWl = @file_get_contents($assetEncFileWl);
        if ($assetEncJsonWl === false || $assetEncJsonWl === '') {
            http_response_code(500);
            echo json_encode(['ok' => false, 'r' => 'read_error']);
            exit;
        }
        echo $assetEncJsonWl;
        exit;
    }
    // SSE heartbeat for whitelisted IPs — must return text/event-stream, not fall through to HTML.
    if ($isLiveSseGet) {
        $wlSseHostChk = (string)($_SERVER['HTTP_HOST'] ?? '');
        if ($og_canonical_host === '' || !og_request_is_origin($C, $wlSseHostChk)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'r' => 'not_origin']);
            exit;
        }
        $rawInputWlSse = (string)file_get_contents('php://input');
        $bodyWlSse = $rawInputWlSse !== '' ? json_decode($rawInputWlSse, true) : null;
        if (!is_array($bodyWlSse) && isset($_GET['p'])) {
            $bodyWlSse = ['p' => (string)$_GET['p']];
        }
        $packetWlSse = is_array($bodyWlSse) ? (string)($bodyWlSse['p'] ?? '') : '';
        $plainWlSse  = $packetWlSse !== '' ? og_b64url_decode($packetWlSse) : null;
        $jWlSse      = $plainWlSse !== null ? json_decode($plainWlSse, true) : null;
        $hostWlSse   = is_array($jWlSse) ? (string)($jWlSse['h'] ?? $wlSseHostChk) : $wlSseHostChk;
        $originWlSse = is_array($jWlSse) ? (string)($jWlSse['o'] ?? '') : '';
        $pageUriWlSse = is_array($jWlSse) ? (string)($jWlSse['u'] ?? '/') : '/';
        $chalWlSse   = is_array($jWlSse) ? (string)($jWlSse['r'] ?? '') : '';
        $ttlBeatWlSse = max(15, min(120, (int)($C['live_session_ttl'] ?? 25)));
        $respWlSse = ['ok' => true, 'alive' => true, 'exp' => $now + $ttlBeatWlSse,
                      'h' => $hostWlSse, 'o' => $originWlSse, 'u' => $pageUriWlSse, 'r' => $chalWlSse, 'wl' => true];
        if (!empty($C['live_key_split'])) {
            $kfTtlWlSse = max(3, min(60, (int)($C['live_kfrag_ttl'] ?? 8)));
            $respWlSse['split'] = 1;
            $respWlSse['kf']    = og_b64url_encode(random_bytes(32));
            $respWlSse['kfb']   = (int)floor($now / $kfTtlWlSse);
            $respWlSse['kft']   = $kfTtlWlSse;
        }
        while (ob_get_level() > 0) { @ob_end_clean(); }
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Connection: close');
        header('X-Accel-Buffering: no');
        $packetOut = og_b64url_encode(json_encode($respWlSse, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        echo 'retry: ' . ($ttlBeatWlSse * 1000) . "\n";
        echo 'data: ' . json_encode(['p' => $packetOut], JSON_UNESCAPED_SLASHES) . "\n\n";
        @flush();
        exit;
    }
    return;
}

// ── Приманки preflight: любой запрос к bait-пути = парсер/префетчер ──
if (og_starts_with($req_path, '/_site/m') || og_starts_with($req_path, '/_og_bait')) {
    $d['preflight_bait_hits'] = (int)($d['preflight_bait_hits'] ?? 0) + 1;
    $d['suspect_reasons'][]  = 'preflight_bait';
    og_perm_ban_add($C['perm_ban_file'], $ip, 'preflight_bait', $rtLog, $uri, 'preflight_bait');
    og_mark_block($d, 'atk', $now + (int)$C['atk_block'], 'preflight_bait', 'preflight_bait', $rtLog, $ip, $uri);
    og_save($d, $ipf);
    $og_fast_block(403);
}

// ── ПЕРМАНЕНТНЫЙ БАН ──────────────────────────────────────────────
if (og_perm_ban_has($C['perm_ban_file'], $ip)) {
    if ($og_is_soft_ban_state($d)) {
        $permRows = is_file($C['perm_ban_file']) ? (file($C['perm_ban_file'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: []) : [];
        $permRows = array_values(array_filter(array_map('trim', $permRows), static function ($row) use ($ip) { return $row !== $ip; }));
        @file_put_contents($C['perm_ban_file'], $permRows ? implode("\n", $permRows) . "\n" : '', LOCK_EX);
        unset($d['perm_banned']);
        og_save($d, $ipf);
    } else {
        $og_fast_block(403);
    }
}

// ── РАННИЙ JS-BEACON: parser/automation сигналы должны писаться до остальных фильтров ──
if ((in_array($req_path, ['/_site/v', '/_og_ping'], true) || $isDirectBeacon) && $method === 'POST') {
    $ping_flag = (string)($_POST['f'] ?? '');
    if ($og_apply_js_flag($ping_flag, $d) !== null) {
        // flag is classified above; only hard automation/parser flags set atk_block
    } else {
        $d['human_score'] = max((float)($d['human_score'] ?? 0), (float)($_POST['s'] ?? 0));
        $d['human_last'] = $now;
    }
    og_save($d, $ipf);
    http_response_code(204);
    exit;
}

// ── SUBNET ЗАЩИТА ─────────────────────────────────────────────────
$subKey  = og_ip_prefix($ip, (int)$C['subnet_mask_v4']);
$subFile = $C['dir'] . '/sub_' . md5($subKey) . '.json';
$subData = is_file($subFile) ? (json_decode(@file_get_contents($subFile), true) ?? []) : [];
$subData['w']   = $subData['w']   ?? $now;
$subData['ips'] = $subData['ips'] ?? [];
if (($now - (int)$subData['w']) > 600) $subData = ['w' => $now, 'ips' => []];
$subData['ips'][$ip] = ($subData['ips'][$ip] ?? 0) + 1;
@file_put_contents($subFile, json_encode($subData), LOCK_EX);
if (count($subData['ips']) >= (int)$C['subnet_limit_10m']) {
    if ($og_browser_shell_candidate) {
        $d['suspect'] = min(100, (int)($d['suspect'] ?? 0) + 10);
        $d['suspect_reasons'][] = 'subnet_limit_soft';
        $d['suspect_last'] = $now;
        og_save($d, $ipf);
    } else {
        og_mark_block($d, 'atk', $now + (int)$C['subnet_block'], 'subnet_limit', 'subnet_guard', $rtLog, $ip, $uri);
        og_save($d, $ipf);
        $og_fast_block(429);
    }
}

// OG internal: heartbeat/live/jsk — не рахуємо в rate-limit (інакше миттєвий бан)
$og_is_og_endpoint = (strpos($req_path, '/_site/') === 0)
    || (basename($req_path) === 'bot-protect.php')
    || (strpos($req_path, '/_og_') === 0);
$og_skip_rl_ts = $og_is_og_endpoint || $isDirectBeacon || $isDirectLiveToken || $isJsKeyGet || $isLiveSseGet || $isAssetGet || $isXlogPost;

// ══════════════════════════════════════════════════════════════════
//  БЛОК 0 — RATE LIMIT (sliding window)
// ══════════════════════════════════════════════════════════════════
$d['ts']   = $d['ts'] ?? [];
if (!$og_skip_rl_ts) {
    $d['ts'][] = $now;
}
$d['ts']   = array_values(array_filter($d['ts'], static function ($t) use ($now) { return $t >= $now - 3600; }));

$c1  = count(array_filter($d['ts'], static function ($t) use ($now) { return $t >= $now - 1; }));
$c60 = count(array_filter($d['ts'], static function ($t) use ($now) { return $t >= $now - 60; }));
$c3k = count($d['ts']);

if ($c1 > $C['rl_sec'] || $c60 > $C['rl_min'] || $c3k > $C['rl_hour']) {
    // Soft: наш JS (навіть якщо canonical_host ще не збігається — og_is_origin=false)
    if ($og_skip_rl_ts || $og_is_origin || $og_browser_shell_candidate || $is_static) {
        $d['suspect'] = min(100, (int)($d['suspect'] ?? 0) + 8);
        $d['suspect_reasons'][] = 'rate_limit_soft';
        $d['suspect_last'] = $now;
        og_save($d, $ipf);
    } else {
        $d['rl_strikes']    = ($d['rl_strikes'] ?? 0) + 1;
        $d['strikes_total'] = ($d['strikes_total'] ?? 0) + 1;
        $d['last_violation']= $now;
        $banFor = $C['rl_block'] * max(1, min(3, (int)$d['rl_strikes']));
        og_mark_block($d, 'rl', $now + $banFor, 'rate_limit:c1=' . $c1 . ',c60=' . $c60 . ',c3k=' . $c3k, 'rate_limit', $rtLog, $ip, $uri);
        og_save($d, $ipf);
        $og_fast_block(429);
    }
}

// ── ФУНКЦИЯ БЛОКИРОВКИ (до БЛОК H: мгновенный suspect вызывает $block) ──
$block = function (string $reason, int $code = 403, string $challenge_html = '') use ($C, $ip, $ua, $ua_l, $uri, $method, $now, &$d, $og_browser_shell_candidate, $og_is_origin): void {
    if ($C['log_on']) {
        if (is_file($C['log']) && filesize($C['log']) > $C['log_max_bytes']) {
            @rename($C['log'], $C['log'] . '.' . date('YmdHis') . '.bak');
        }
        og_log_extended($C['log'], $ip, $ua, $uri, $reason, $code, $method, [
            'suspect'     => $d['suspect'] ?? '-',
            'human_score' => $d['human_score'] ?? '-',
            'timing_std'  => $d['timing_std'] ?? '-',
        ]);
    }
    // Diagnostic header: видна в DevTools → Network → headers respons'у.
    // Безпечно: причина — статичні рядки з білого списку gate'ів, не містить
    // user-input. Допомагає швидко знайти чому 403 без копання у логах сервера.
    if (!headers_sent()) {
        header('X-OG-Reason: ' . preg_replace('/[^a-zA-Z0-9_:\-.]/', '_', substr($reason, 0, 80)));
    }
    if ($og_browser_shell_candidate && in_array($code, [403, 429, 503], true)) {
        og_blank_deny_response();
    }
    http_response_code($code);
    header('Cache-Control: no-store, no-cache');
    header('X-Robots-Tag: noindex');

    $is_bot = empty(trim($ua)) || preg_match(
        '/python|curl|wget|scrapy|go-http|java\/|okhttp|axios|node-fetch|undici|aiohttp|httpx|requests|urllib|http\.client|httpclient|libwww|headless|phantom|selenium|webdriver|puppeteer|playwright|cypress|nightmare|bright\s*data|oxylabs|smartproxy|webscraping|gptbot|claudebot|bytespider|httrack|teleport|scrapingbee|crawlera|colly\//i',
        $ua
    );

    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title> </title><style>html,body{margin:0;background:#fff}</style></head><body></body></html>';
    exit;
};

// ── REMOTE GATE: центральный encrypted webhook может оборвать открытие ленда ──
if (!empty($C['remote_gate_on']) && og_remote_gate_candidate($method, $uri)) {
    $denyUntil  = (int)($d['remote_gate_deny_until'] ?? 0);
    $allowUntil = (int)($d['remote_gate_allow_until'] ?? 0);

    if ($denyUntil > $now) {
        $block('remote_gate_denied_cached', 403);
    }

    if ($allowUntil <= $now) {
        $gate = og_remote_gate_call($C, $ip, $ua, $uri, $method, $d, $now);
        if (!empty($gate['ok'])) {
            if (!empty($gate['allow'])) {
                $d['remote_gate_allow_until'] = $now + (int)$gate['ttl'];
                unset($d['remote_gate_deny_until'], $d['remote_gate_error']);
                og_save($d, $ipf);
            } else {
                $ttl = (int)($gate['ttl'] ?: ($C['remote_gate_deny_ttl'] ?? 3600));
                $d['remote_gate_deny_until'] = $now + $ttl;
                $d['remote_gate_reason'] = $gate['reason'] ?: 'remote_gate_denied';
                $d['suspect_reasons'][] = 'remote_gate:' . $d['remote_gate_reason'];
                if (!empty($gate['perm'])) {
                    og_perm_ban_add($C['perm_ban_file'], $ip, 'remote_gate:' . $d['remote_gate_reason'], $rtLog, $uri, 'remote_gate');
                    $d['perm_banned'] = 1;
                }
                og_save($d, $ipf);
                $block('remote_gate_denied:' . $d['remote_gate_reason'], 403);
            }
        } else {
            $d['remote_gate_error'] = $gate['error'] ?? 'unknown';
            $d['remote_gate_error_t'] = $now;
            og_save($d, $ipf);
            if (!empty($C['remote_gate_fail_closed'])) {
                $block('remote_gate_unavailable', 503);
            }
        }
    }
}

// POST: прохождение mini-honeypot — выдаём HttpOnly cookie, редирект на ленд
$isDirectPreflight = $method === 'POST'
    && basename($req_path) === 'bot-protect.php'
    && ((string)($_GET['_og_ep'] ?? '') === 'r' || isset($_POST['_ogpfn']));
if ($method === 'POST' && (in_array($req_path, ['/_site/r', '/_og_pf_ok'], true) || $isDirectPreflight)) {
    $ua_ck_pf = substr(hash('sha256', $ua), 0, 10);
    $pf_expect = substr(hash_hmac('sha256', $ip . '|' . $ua_ck_pf . '|ogpf|' . date('YmdH'), $C['cookie_secret']), 0, 20);
    $hp = trim((string)($_POST['_ogpf_email'] ?? ''));
    if ($hp !== '') {
        og_perm_ban_add($C['perm_ban_file'], $ip, 'preflight_hp_trip', $rtLog, $uri, 'preflight_honeypot');
        og_mark_block($d, 'atk', $now + (int)$C['atk_block'], 'preflight_hp_trip', 'preflight_honeypot', $rtLog, $ip, $uri);
        $d['suspect_reasons'][] = 'preflight_hp_trip';
        og_save($d, $ipf);
        $block('preflight_hp_trip', 403);
    }
    $tok = (string)($_POST['_ogpfn'] ?? '');
    if ($tok === '' || empty($d['pf_nonce']) || !hash_equals((string)$d['pf_nonce'], $tok)) {
        $d['suspect'] = min(100, (int)($d['suspect'] ?? 0) + 20);
        $d['suspect_reasons'][] = 'preflight_bad_token';
        unset($d['pf_nonce'], $d['pf_nonce_t']);
        og_save($d, $ipf);
        header('Location: /', true, 302);
        exit;
    }
    unset($d['pf_nonce'], $d['pf_nonce_t']);
    $ttl     = (int)($C['preflight_ttl'] ?? 86400);
    $pf_name = (string)($C['preflight_cookie'] ?? '_ogpf');
    $secure  = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    setcookie($pf_name, $pf_expect, [
        'expires'  => $now + $ttl,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => $secure,
    ]);
    $r = (string)($_POST['r'] ?? '/');
    if ($r === '' || $r[0] !== '/' || strlen($r) > 2048 || strpos($r, '://') !== false) {
        $r = '/';
    }
    og_save($d, $ipf);
    header('Location: ' . $r, true, 302);
    exit;
}

// ══════════════════════════════════════════════════════════════════
//  /_site/a — Encrypted asset serving (origin-authenticated only)
// ══════════════════════════════════════════════════════════════════
if ($isAssetGet) {
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('X-Robots-Tag: noindex');
    header('Content-Type: application/json; charset=utf-8');
    $assetHost = (string)($_SERVER['HTTP_HOST'] ?? '');
    og_xlog('debug', 'asset_get', 'entry', ['host' => $assetHost, 'f' => substr((string)($_GET['f'] ?? ''), 0, 16), 'tok_len' => strlen((string)($_SERVER['HTTP_X_OG_TOKEN'] ?? ''))]);
    if ($og_canonical_host === '' || !og_request_is_origin($C, $assetHost)) {
        og_xlog('warn', 'asset_get', 'reject:not_origin', ['canon' => $og_canonical_host, 'host' => $assetHost, 'referer' => substr((string)($_SERVER['HTTP_REFERER'] ?? ''), 0, 80)]);
        http_response_code(403);
        echo json_encode(['ok' => false, 'r' => 'not_origin']);
        exit;
    }
    $assetPub = trim((string)($_SERVER['HTTP_X_OG_TOKEN'] ?? ''));
    if ($assetPub === '' && isset($_GET['tok'])) {
        $assetPub = trim((string)$_GET['tok']);
    }
    if ($assetPub === '' || !og_live_pub_well_formed($assetPub)) {
        og_xlog('warn', 'asset_get', 'reject:no_token', ['pub_len' => strlen($assetPub), 'well_formed' => $assetPub !== '' ? og_live_pub_well_formed($assetPub) : false]);
        http_response_code(403);
        echo json_encode(['ok' => false, 'r' => 'no_token']);
        exit;
    }
    $assetPubRow = og_live_pub_row($d, $C, $assetPub, $now, $ip);
    if ($assetPubRow === null) {
        og_xlog('warn', 'asset_get', 'reject:invalid_session', ['pub' => substr($assetPub, 0, 10)]);
        http_response_code(403);
        echo json_encode(['ok' => false, 'r' => 'invalid_session']);
        exit;
    }
    $assetFHash = preg_replace('/[^a-f0-9]/', '', strtolower((string)($_GET['f'] ?? '')));
    if (strlen($assetFHash) < 8 || strlen($assetFHash) > 64) {
        og_xlog('warn', 'asset_get', 'reject:bad_hash', ['hash_len' => strlen($assetFHash), 'raw' => substr((string)($_GET['f'] ?? ''), 0, 30)]);
        http_response_code(400);
        echo json_encode(['ok' => false, 'r' => 'bad_hash']);
        exit;
    }
    $assetDir = __DIR__ . '/__OG_ASSETS_SUBDIR__';
    $assetEncFile = $assetDir . '/' . $assetFHash . '.enc';
    if (!is_file($assetEncFile) || !@is_readable($assetEncFile)) {
        og_xlog('error', 'asset_get', 'reject:not_found', ['file' => basename($assetEncFile), 'is_file' => is_file($assetEncFile), 'readable' => @is_readable($assetEncFile)]);
        http_response_code(404);
        echo json_encode(['ok' => false, 'r' => 'not_found']);
        exit;
    }
    $assetEncJson = @file_get_contents($assetEncFile);
    if ($assetEncJson === false || $assetEncJson === '') {
        og_xlog('error', 'asset_get', 'read_error', ['file' => basename($assetEncFile), 'size' => @filesize($assetEncFile)]);
        http_response_code(500);
        echo json_encode(['ok' => false, 'r' => 'read_error']);
        exit;
    }
    og_xlog('info', 'asset_get', 'serve_ok', ['hash' => substr($assetFHash, 0, 10), 'bytes' => strlen($assetEncJson)]);
    echo $assetEncJson;
    exit;
}

// POST: live-token для одностраничных HTML — копия без сервера/свежих cookies не разблокируется
// GET допускается только для SSE-heartbeat и origin-gated jsk (ключ зашифрованного JS-бандла).
if (($method === 'POST' && ($req_path === $liveEndpoint || $req_path === '/_og_page_token' || $isDirectLiveToken))
    || $isLiveSseGet || $isJsKeyGet) {
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('X-Robots-Tag: noindex, nofollow');
    header('Content-Type: application/json; charset=utf-8');

    $host = (string)($_SERVER['HTTP_HOST'] ?? '');

    if ($isJsKeyGet) {
        // Ключ зашифрованного JS-гвард-бандла выдаётся ТОЛЬКО на каноническом origin.
        // Копия (file://, чужой хост, нет сервера) сюда не дотянется → бандл не расшифровать.
        if (!og_request_is_origin($C, $host)) {
            if (!headers_sent()) header('X-OG-Reason: jsk_not_origin');
            http_response_code(403);
            echo json_encode(['ok' => false, 'r' => 'not_origin']);
            exit;
        }
        $__jksSec = '__OG_SECRET_PATH__';
        if ($__jksSec === '' || !@is_readable($__jksSec) || strlen((string)@file_get_contents($__jksSec)) < 32) {
            if (!headers_sent()) header('X-OG-Reason: jsk_no_secret');
            http_response_code(403);
            echo json_encode(['ok' => false, 'r' => 'no_secret']);
            exit;
        }
        $lcJs = og_canonical_host_cfg($C);
        $masterJs = trim((string)($C['live_payload_key'] ?? ''));
        // NB: не сверяем с литералом OG_PAYLOAD_KEY_CHANGE_ME — write-time str_replace
        // подменяет такой литерал реальным ключом. Патчер всегда подставляет реальный ключ.
        if ($lcJs === '' || strlen($masterJs) < 16) {
            if (!headers_sent()) header('X-OG-Reason: jsk_no_key');
            http_response_code(404);
            echo json_encode(['ok' => false, 'r' => 'no_key']);
            exit;
        }
        $jsKey = hash_hmac('sha256', 'OfferGuardJsBundleV1|' . $lcJs, og_payload_key_bytes($masterJs), true);
        echo json_encode(['ok' => true, 'jk' => og_b64url_encode($jsKey)]);
        exit;
    }
    $uaHashLive = substr(hash('sha256', $ua), 0, 10);
    $ogvExpected = substr(hash_hmac('sha256', $ip . '|' . $uaHashLive . '|' . date('YmdH'), $C['cookie_secret']), 0, 16);
    $pfExpected  = substr(hash_hmac('sha256', $ip . '|' . $uaHashLive . '|ogpf|' . date('YmdH'), $C['cookie_secret']), 0, 20);
    $ckNameLive  = (string)($C['cookie_name'] ?? '_ogv');
    $pfNameLive  = (string)($C['preflight_cookie'] ?? '_ogpf');

    $rawInput = (string)file_get_contents('php://input');
    $body = $rawInput !== '' ? json_decode($rawInput, true) : null;
    if (!is_array($body) && isset($_POST['p'])) {
        $body = ['p' => (string)$_POST['p']];
    }
    if (!is_array($body) && isset($_GET['p'])) {
        // SSE heartbeat (EventSource is GET-only) carries the packet as a query param.
        $body = ['p' => (string)$_GET['p']];
    }
    $packet = is_array($body) ? (string)($body['p'] ?? '') : '';
    $plain = $packet !== '' ? og_b64url_decode($packet) : null;
    $j = $plain !== null ? json_decode($plain, true) : null;
    $action = is_array($j) ? (string)($j['a'] ?? 'issue') : 'issue';
    if (!in_array($action, ['issue', 'confirm', 'beat', 'revoke', 'validate'], true)) {
        $action = 'issue';
    }

    // Copy/mirror: validate → decoy; mint/confirm/beat/revoke/issue → deny (+ ban stale pub, no origin grace).
    if ($og_canonical_host !== '' && og_webhook_request_is_copy($C, $host)) {
        if ($action === 'validate') {
            og_live_copy_validate_decoy_response($C, $now);
            exit;
        }
        if (in_array($action, ['issue', 'confirm', 'beat', 'revoke'], true)) {
            og_live_copy_gate_deny($C, $d, $ip, $uri, $rtLog, $now, $action, is_array($j) ? $j : null, $ipf);
        }
    }

    $cookieOk = true;
    if (!empty($C['live_token_require_cookies'])) {
        $pfOk = empty($C['preflight_on']) || $og_is_origin
            || (!empty($_COOKIE[$pfNameLive]) && hash_equals($pfExpected, (string)$_COOKIE[$pfNameLive]));
        $cookieOk = !empty($_COOKIE[$ckNameLive]) && hash_equals($ogvExpected, (string)$_COOKIE[$ckNameLive]) && $pfOk;
    }

    $pageUri = is_array($j) ? (string)($j['u'] ?? '') : '';
    $origin  = is_array($j) ? (string)($j['o'] ?? '') : '';
    $originScheme = strtolower((string)(parse_url($origin, PHP_URL_SCHEME) ?: ''));
    $originHost = (string)(parse_url($origin, PHP_URL_HOST) ?: '');
    $originPort = parse_url($origin, PHP_URL_PORT);
    if ($originHost !== '' && $originPort) $originHost .= ':' . $originPort;
    $challenge = is_array($j) ? (string)($j['r'] ?? '') : '';
    $fpLive = is_array($j) ? substr((string)($j['fp'] ?? ''), 0, 128) : '';
    $clientTs = is_array($j) ? (int)($j['t'] ?? 0) : 0;
    $ageMs = abs((int)round(microtime(true) * 1000) - $clientTs);
    $reqTtlMs = max(5, min(120, (int)($C['live_token_request_ttl'] ?? 30))) * 1000;
    $originHeader = (string)($_SERVER['HTTP_ORIGIN'] ?? '');
    $refererHost = '';
    if (!empty($_SERVER['HTTP_REFERER'])) {
        $refererHost = (string)(parse_url((string)$_SERVER['HTTP_REFERER'], PHP_URL_HOST) ?: '');
        $refererPort = parse_url((string)$_SERVER['HTTP_REFERER'], PHP_URL_PORT);
        if ($refererHost !== '' && $refererPort) $refererHost .= ':' . $refererPort;
    }
    $fetchSite = strtolower((string)($_SERVER['HTTP_SEC_FETCH_SITE'] ?? ''));

    $challengeOk = $challenge !== ''
        && strlen($challenge) >= 8
        && strlen($challenge) <= 80
        && preg_match('/^[a-zA-Z0-9._-]+$/', $challenge);
    if ($action === 'validate') {
        $challengeOk = true;
    }

    $ok = is_array($j)
        && og_host_matches_canonical((string)($j['h'] ?? ''), $host)
        && in_array($originScheme, ['http', 'https'], true)
        && ($originHost === '' || og_host_matches_canonical($originHost, $host))
        && $pageUri !== ''
        && $pageUri[0] === '/'
        && strlen($pageUri) <= 2048
        && strpos($pageUri, '://') === false
        && !og_starts_with($pageUri, '/_site/')
        && $challengeOk
        && $clientTs > 0
        && $ageMs <= $reqTtlMs
        && ($originHeader === '' || hash_equals($origin, $originHeader))
        && (
            $refererHost === ''
            || og_host_matches_canonical($refererHost, $host)
            || og_request_is_origin($C, $host)
        )
        && (
            $fetchSite === ''
            || in_array($fetchSite, ['same-origin', 'none', 'cross-site'], true)
            || og_request_is_origin($C, $host)
        )
        && $cookieOk;

    if (!empty($d['remote_gate_deny_until']) && (int)$d['remote_gate_deny_until'] > $now) {
        $ok = false;
    }
    if ((!empty($d['atk_block']) && (int)$d['atk_block'] > $now) || (!empty($d['rl_block']) && (int)$d['rl_block'] > $now)) {
        if (!$og_is_origin) {
            $ok = false;
        }
    }
    if ($og_canonical_host !== '') {
        if (!og_host_matches_canonical($host, $og_canonical_host)) {
            $ok = false;
        }
        if ($originHost !== '' && !og_host_matches_canonical($originHost, $og_canonical_host)) {
            $ok = false;
        }
        if ($originHeader !== '') {
            $og_hdr_origin_host = (string)(parse_url($originHeader, PHP_URL_HOST) ?: '');
            $og_hdr_origin_port = parse_url($originHeader, PHP_URL_PORT);
            if ($og_hdr_origin_host !== '' && $og_hdr_origin_port) {
                $og_hdr_origin_host .= ':' . $og_hdr_origin_port;
            }
            if ($og_hdr_origin_host !== '' && !og_host_matches_canonical($og_hdr_origin_host, $og_canonical_host)) {
                $ok = false;
            }
        }
        if ($refererHost !== '' && !og_host_matches_canonical($refererHost, $og_canonical_host)
            && !og_request_is_origin($C, $host)) {
            $ok = false;
        }
        // Host-аттестация: ha = b64url("location.host|nonce"). Тупой sed-прокси перепишет
        // видимый h, но непрозрачный ha — нет → ловим reverse-proxy/копию + лог домена.
        $haRaw = is_array($j) ? (string)($j['ha'] ?? '') : '';
        if ($haRaw !== '') {
            $haDec = (string)(og_b64url_decode($haRaw) ?? '');
            $haParts = explode('|', $haDec, 2);
            $haHost = strtolower(trim((string)($haParts[0] ?? '')));
            $haNonce = (string)($haParts[1] ?? '');
            if ($haHost === '' || !og_host_matches_canonical($haHost, $og_canonical_host)
                || ($challenge !== '' && $haNonce !== $challenge)) {
                $ok = false;
                @error_log('[OfferGuard] host-attest mismatch ip=' . $ip
                    . ' reported=' . substr($haHost, 0, 80) . ' canon=' . $og_canonical_host
                    . ' uri=' . substr($uri, 0, 120));
                og_try_ban_bad_live_pub_guarded($C, $d, $ip, $uri, 'host_attest_proxy', $rtLog, $now, $host, '');
            }
        }
    }

    if (!$ok) {
        $d['live_token_fail'] = min(3, (int)($d['live_token_fail'] ?? 0) + 1);
        $d['live_token_last_fail'] = $now;
        og_save($d, $ipf);
        http_response_code(200);
        $denyReason = 'live_token_failed';
        if (og_perm_ban_has($C['perm_ban_file'], $ip) || (!empty($d['atk_block']) && (int)$d['atk_block'] > $now)) {
            $denyReason = 'ip_banned';
        } elseif (!empty($d['rl_block']) && (int)$d['rl_block'] > $now) {
            $denyReason = 'rate_limited';
        }
        echo json_encode(['p' => og_b64url_encode(json_encode(['ok' => false, 'blocked' => true, 'reason' => $denyReason], JSON_UNESCAPED_SLASHES))]);
        exit;
    }

    $hookKey = og_live_hook_key($host, $origin, $pageUri, $challenge);
    $tabId = is_array($j) ? trim((string)($j['tid'] ?? '')) : '';
    og_live_hook_prune($d, $now);

    if ($action === 'validate') {
        $pubIn = trim((string)($j['pub'] ?? ''));
        if ($pubIn === '' && !empty($_SERVER['HTTP_X_OG_TOKEN'])) {
            $pubIn = trim((string)$_SERVER['HTTP_X_OG_TOKEN']);
        }
        $pubRow = $pubIn !== '' ? og_live_pub_row($d, $C, $pubIn, $now, $ip) : null;
        $valid = $pubRow !== null;
        if ($pubIn !== '' && !$valid) {
            if (!og_live_pub_well_formed($pubIn)) {
                og_try_ban_bad_live_pub_guarded($C, $d, $ip, $uri, 'validate_malformed', $rtLog, $now, $host, $pubIn);
            } else {
                og_try_ban_bad_live_pub_guarded($C, $d, $ip, $uri, 'validate_bad', $rtLog, $now, $host, $pubIn);
            }
        }
        if ($valid) {
            $whOk = og_webhook_validate_sync($C, $pubIn, $ip, $host, is_array($j) ? (string)($j['sid'] ?? '') : '');
            if ($whOk === false) {
                $valid = false;
            } elseif ($whOk === null && !empty($C['og_webhook_validate'])) {
                og_webhook_live_event($C, 'validate', $pubIn, $ip, ['host' => $host, 'sid' => is_array($j) ? (string)($j['sid'] ?? '') : '']);
            }
        }
        og_save($d, $ipf);
        $vPayload = array_merge(['ok' => true, 'valid' => $valid, 'accepted' => $valid], og_live_family_payload($C, $host));
        echo json_encode(['p' => og_b64url_encode(json_encode($vPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))]);
        exit;
    }

    if ($action === 'revoke') {
        $pubIn = trim((string)($j['pub'] ?? ''));
        if ($pubIn === '' && !empty($_SERVER['HTTP_X_OG_TOKEN'])) {
            $pubIn = trim((string)$_SERVER['HTTP_X_OG_TOKEN']);
        }
        $sid = is_array($j) ? (string)($j['sid'] ?? '') : '';
        $sidHashR = $sid !== '' ? hash_hmac('sha256', $sid, (string)$C['cookie_secret']) : '';
        $pubRow = $pubIn !== '' ? og_live_pub_row($d, $C, $pubIn, $now, $ip) : null;
        $did = false;
        if ($pubIn !== '' && $pubRow === null) {
            $revokeBanned = false;
            if (!og_live_pub_well_formed($pubIn)) {
                $revokeBanned = og_try_ban_bad_live_pub_guarded($C, $d, $ip, $uri, 'revoke_malformed', $rtLog, $now, $host, $pubIn);
            } else {
                $revokeBanned = og_try_ban_bad_live_pub_guarded($C, $d, $ip, $uri, 'revoke_unknown_pub', $rtLog, $now, $host, $pubIn);
            }
            og_save($d, $ipf);
            if ($revokeBanned) {
                echo json_encode(['p' => og_b64url_encode(json_encode(['ok' => false, 'blocked' => true, 'reason' => 'live_token_blocked'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))]);
            } else {
                echo json_encode(['p' => og_b64url_encode(json_encode(['ok' => true, 'revoked' => false], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))]);
            }
            exit;
        }
        if ($pubRow !== null && $sidHashR !== '' && hash_equals((string)($pubRow['sidh'] ?? ''), $sidHashR)) {
            $did = true;
            unset($d['live_pub_tokens'][(string)($pubRow['_k'] ?? '')]);
            og_live_pub_detach_all_for_sid($d, $sidHashR);
            og_live_session_clear($d, $sidHashR);
            if (!empty($C['live_hook_one_tab'])) {
                $revHook = is_array($j) ? og_live_hook_key($host, $origin, $pageUri, $challenge) : '';
                if ($revHook !== '') {
                    og_live_hook_release($d, $revHook, $tabId, $sidHashR);
                }
            }
            og_webhook_live_event($C, 'revoke', $pubIn, $ip, ['host' => $host, 'sid' => $sid]);
        }
        og_save($d, $ipf);
        echo json_encode(['p' => og_b64url_encode(json_encode(['ok' => true, 'revoked' => $did], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))]);
        exit;
    }

    $ttl = max(30, min(600, (int)($C['live_token_ttl'] ?? 120)));
    $ttlBeat = max(15, min(120, (int)($C['live_session_ttl'] ?? 25)));
    $hookLeaderTtl = max($ttlBeat * 4, 120);
    $bucket = (int)floor($now / $ttl);
    $tokenSlot = substr(hash('sha256', strtolower($host) . '|' . strtolower($origin) . '|' . $pageUri . '|' . $challenge), 0, 32);
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $d['live_sessions'] = is_array($d['live_sessions'] ?? null) ? $d['live_sessions'] : [];
    foreach ($d['live_sessions'] as $k => $row) {
        if (!is_array($row) || (int)($row['exp'] ?? 0) < $now) unset($d['live_sessions'][$k]);
    }
    if (count($d['live_sessions']) > 32) {
        $d['live_sessions'] = array_slice($d['live_sessions'], -32, null, true);
    }

    if ($action === 'beat') {
        $sid = is_array($j) ? (string)($j['sid'] ?? '') : '';
        $sidHash = $sid !== '' ? hash_hmac('sha256', $sid, (string)$C['cookie_secret']) : '';
        if (!empty($C['live_hook_one_tab'])) {
            if (!og_live_tab_id_valid($tabId)) {
                og_save($d, $ipf);
                http_response_code(200);
                echo json_encode(['p' => og_b64url_encode(json_encode(['ok' => false, 'blocked' => true, 'reason' => 'tab_limit'], JSON_UNESCAPED_SLASHES))]);
                exit;
            }
            if (!og_live_hook_is_leader($d, $hookKey, $tabId, $sidHash, $now)) {
                if ($og_is_origin) {
                    // Origin: promote this tab as leader (reload/reopen within TTL)
                    og_live_hook_prune($d, $now);
                    $d['live_hook_tabs'][$hookKey] = ['tab' => $tabId, 'sidh' => $sidHash, 'pubk' => '', 'exp' => $now + $hookLeaderTtl];
                    og_runtime_log($rtLog, $ip, $uri, 'origin_hook_promote_beat:' . og_log_field($hookKey, 16));
                } else {
                    og_try_ban_bad_live_pub($C, $d, $ip, $uri, 'beat_tab_limit', $rtLog, $now);
                    og_save($d, $ipf);
                    http_response_code(200);
                    echo json_encode(['p' => og_b64url_encode(json_encode(['ok' => false, 'blocked' => true, 'reason' => 'tab_limit'], JSON_UNESCAPED_SLASHES))]);
                    exit;
                }
            }
        }
        $fpHash = substr(hash('sha256', $fpLive), 0, 24);
        $session = $sidHash !== '' && is_array($d['live_sessions'][$sidHash] ?? null) ? $d['live_sessions'][$sidHash] : null;
        if ($session === null && $sidHash !== '' && !empty($d['live_session_hash']) && hash_equals((string)$d['live_session_hash'], $sidHash)) {
            $session = [
                'exp' => (int)($d['live_session_exp'] ?? 0),
                'last' => (int)($d['live_session_last'] ?? 0),
                'host' => (string)($d['live_session_host'] ?? ''),
                'origin' => (string)($d['live_session_origin'] ?? ''),
                'uri' => (string)($d['live_session_uri'] ?? ''),
                'challenge' => (string)($d['live_session_challenge'] ?? ''),
                'fp' => (string)($d['live_session_fp'] ?? ''),
            ];
        }
        $httpPub = trim((string)($_SERVER['HTTP_X_OG_TOKEN'] ?? ''));
        if ($httpPub === '' && is_array($j)) {
            $httpPub = trim((string)($j['pub'] ?? ''));
        }
        $alive = is_array($session)
            && (int)($session['exp'] ?? 0) >= $now
            && (string)($session['host'] ?? '') === $host
            && (string)($session['origin'] ?? '') === $origin
            && (string)($session['uri'] ?? '') === $pageUri
            && (string)($session['challenge'] ?? '') === $challenge
            && (string)($session['fp'] ?? '') === $fpHash;
        $needPub = is_array($session) && !empty($session['pubk']);
        if ($needPub) {
            $pr = $httpPub !== '' ? og_live_pub_row($d, $C, $httpPub, $now, $ip) : null;
            if ($httpPub === '') {
                $alive = false;
            } elseif ($pr === null || !hash_equals((string)($pr['sidh'] ?? ''), $sidHash)) {
                if (!og_live_pub_well_formed($httpPub)) {
                    og_try_ban_bad_live_pub_guarded($C, $d, $ip, $uri, 'beat_malformed', $rtLog, $now, $host, $httpPub);
                } else {
                    og_try_ban_bad_live_pub_guarded($C, $d, $ip, $uri, 'beat_bad_sid_or_revoked', $rtLog, $now, $host, $httpPub);
                }
                if ($sidHash !== '') {
                    unset($d['live_sessions'][$sidHash]);
                }
                og_save($d, $ipf);
                http_response_code(200);
                echo json_encode(['p' => og_b64url_encode(json_encode(['ok' => false, 'blocked' => true, 'reason' => 'live_token_blocked'], JSON_UNESCAPED_SLASHES))]);
                exit;
            }
        }

        if (!$alive) {
            if ($sidHash !== '') unset($d['live_sessions'][$sidHash]);
            og_save($d, $ipf);
            http_response_code(200);
            echo json_encode(['p' => og_b64url_encode(json_encode(['ok' => false, 'blocked' => true, 'reason' => 'live_token_dead'], JSON_UNESCAPED_SLASHES))]);
            exit;
        }

        $splitOn = !empty($C['live_key_split']);
        $sseReq = $splitOn && !empty($C['live_sse']) && !empty($_GET['sse']);
        if ($splitOn) {
            $minGap = max(1, (int)($C['live_beat_min_gap'] ?? 2));
            $kfLast = (int)($session['kf_last'] ?? 0);
            if ($kfLast > 0 && ($now - $kfLast) < $minGap) {
                og_try_ban_bad_live_pub_guarded($C, $d, $ip, $uri, 'beat_keysplit_rate', $rtLog, $now, $host, $httpPub);
                if ($sidHash !== '') unset($d['live_sessions'][$sidHash]);
                og_save($d, $ipf);
                http_response_code(200);
                echo json_encode(['p' => og_b64url_encode(json_encode(['ok' => false, 'blocked' => true, 'reason' => 'live_key_dead'], JSON_UNESCAPED_SLASHES))]);
                exit;
            }
        }

        $session['exp'] = $now + $ttlBeat;
        $session['last'] = $now;
        $d['live_sessions'][$sidHash] = $session;
        $d['live_session_hash'] = $sidHash;
        $d['live_session_exp'] = $session['exp'];
        $d['live_session_last'] = $now;
        $d['live_session_host'] = (string)$session['host'];
        $d['live_session_origin'] = (string)$session['origin'];
        $d['live_session_uri'] = (string)$session['uri'];
        $d['live_session_challenge'] = (string)$session['challenge'];
        $d['live_session_fp'] = (string)$session['fp'];
        if (!empty($C['live_hook_one_tab']) && og_live_tab_id_valid($tabId)) {
            og_live_hook_touch($d, $hookKey, $now, $hookLeaderTtl);
        }
        $kfragOut = null;
        $kfBucketOut = 0;
        $kfTtlOut = 0;
        if ($splitOn) {
            if (!og_request_is_origin($C, $host)) {
                og_try_ban_bad_live_pub_guarded($C, $d, $ip, $uri, 'beat_keysplit_offorigin', $rtLog, $now, $host, $httpPub);
                if ($sidHash !== '') unset($d['live_sessions'][$sidHash]);
                og_save($d, $ipf);
                http_response_code(200);
                echo json_encode(['p' => og_b64url_encode(json_encode(['ok' => false, 'blocked' => true, 'reason' => 'live_key_dead'], JSON_UNESCAPED_SLASHES))]);
                exit;
            }
            $kfTtlOut = max(3, min(60, (int)($C['live_kfrag_ttl'] ?? 8)));
            $kfBucketOut = (int)floor($now / $kfTtlOut);
            $kfragOut = random_bytes(32);
            $session['kfrag_h'] = hash_hmac('sha256', $kfragOut, (string)$C['cookie_secret']);
            $session['kfrag_b'] = $kfBucketOut;
            $session['kfrag_exp'] = $now + $kfTtlOut;
            $session['kf_last'] = $now;
            $d['live_sessions'][$sidHash] = $session;
        }
        $resp = array_merge(
            ['ok' => true, 'alive' => true, 'exp' => $now + $ttlBeat, 'h' => $host, 'o' => $origin, 'u' => $pageUri, 'r' => $challenge],
            og_live_family_payload($C, $host)
        );
        if ($splitOn && $kfragOut !== null) {
            $resp['split'] = 1;
            $resp['kf'] = og_b64url_encode($kfragOut);
            $resp['kfb'] = $kfBucketOut;
            $resp['kft'] = $kfTtlOut;
        }
        if ($needPub && $httpPub !== '' && $pr !== null) {
            $pk = (string)($pr['_k'] ?? '');
            $pubTtl = og_live_pub_ttl_secs($C);
            $pubExp = (int)($pr['exp'] ?? 0);
            $pubIat = (int)($pr['iat'] ?? 0);
            $rotate = !empty($C['live_pub_rotate'])
                && ($pubExp - $now < 15 || ($pubIat > 0 && ($now - $pubIat) >= max(15, $pubTtl - 15)));
            if ($rotate && $pk !== '' && $sidHash !== '') {
                $minted = og_live_pub_mint($d, $C, $sidHash, $ip, $now);
                $final = og_webhook_mint_sync($d, $C, $minted, $sidHash, $ip, $now, $host, $sid, 'rotate');
                if ($final !== null) {
                    unset($d['live_pub_tokens'][$pk]);
                    $session['pubk'] = $final['k'];
                    $d['live_sessions'][$sidHash] = $session;
                    $resp['g'] = $final['pub'];
                    $resp['gexp'] = $final['exp'];
                } elseif (isset($d['live_pub_tokens'][$minted['k']])) {
                    unset($d['live_pub_tokens'][$minted['k']]);
                }
            } elseif ($pk !== '' && !empty($d['live_pub_tokens'][$pk]) && is_array($d['live_pub_tokens'][$pk])) {
                $d['live_pub_tokens'][$pk]['exp'] = $now + $pubTtl;
            }
            if ($httpPub !== '' && $pr !== null) {
                $whOk = og_webhook_validate_sync($C, $httpPub, $ip, $host, $sid, true);
                if ($whOk === false) {
                    if (og_webhook_request_is_copy($C, $host)) {
                        og_try_ban_bad_live_pub($C, $d, $ip, $uri, 'beat_webhook_invalid', $rtLog, $now);
                        unset($d['live_sessions'][$sidHash]);
                        og_save($d, $ipf);
                        http_response_code(200);
                        echo json_encode(['p' => og_b64url_encode(json_encode(['ok' => false, 'blocked' => true, 'reason' => 'live_token_blocked'], JSON_UNESCAPED_SLASHES))]);
                        exit;
                    }
                    @error_log('[OfferGuard] beat webhook validate denied — keeping pub (origin-safe)');
                } elseif ($whOk === null && !empty($C['og_webhook_validate'])) {
                    og_webhook_live_event($C, 'validate', $httpPub, $ip, ['host' => $host, 'sid' => $sid]);
                }
            }
        }
        og_save($d, $ipf);
        if ($sseReq && $kfragOut !== null) {
            @set_time_limit(0);
            @ignore_user_abort(false);
            while (ob_get_level() > 0) { @ob_end_clean(); }
            header('Content-Type: text/event-stream; charset=utf-8');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Connection: close');
            header('X-Accel-Buffering: no');
            $emit = static function (array $r, int $ttl): void {
                $packet = og_b64url_encode(json_encode($r, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                echo 'retry: ' . ($ttl * 1000) . "\n";
                echo 'data: ' . json_encode(['p' => $packet], JSON_UNESCAPED_SLASHES) . "\n\n";
                @flush();
            };
            $emit($resp, $kfTtlOut);
            for ($it = 0; $it < 20; $it++) {
                for ($s = 0; $s < $kfTtlOut; $s++) {
                    sleep(1);
                    if (connection_aborted()) { exit; }
                }
                $now2 = time();
                $kfBucket2 = (int)floor($now2 / $kfTtlOut);
                $kfrag2 = random_bytes(32);
                $session['kfrag_h'] = hash_hmac('sha256', $kfrag2, (string)$C['cookie_secret']);
                $session['kfrag_b'] = $kfBucket2;
                $session['kfrag_exp'] = $now2 + $kfTtlOut;
                $session['kf_last'] = $now2;
                $session['exp'] = $now2 + $ttlBeat;
                $session['last'] = $now2;
                $d['live_sessions'][$sidHash] = $session;
                og_save($d, $ipf);
                $emit([
                    'ok' => true, 'alive' => true, 'exp' => $now2 + $ttlBeat,
                    'h' => $host, 'o' => $origin, 'u' => $pageUri, 'r' => $challenge,
                    'split' => 1, 'kf' => og_b64url_encode($kfrag2), 'kfb' => $kfBucket2, 'kft' => $kfTtlOut,
                ], $kfTtlOut);
                if (connection_aborted()) { exit; }
            }
            exit;
        }
        echo json_encode(['p' => og_b64url_encode(json_encode($resp, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))]);
        exit;
    }

    if ($action === 'confirm') {
        // Re-read fresh state under lock to avoid slot loss from concurrent requests
        // (issue stores slot, then a concurrent page/asset request overwrites the file without it).
        $_confirmLock = og_ip_lock($ipf);
        $d = is_file($ipf) ? (json_decode(@file_get_contents($ipf), true) ?? []) : [];
        og_live_hook_prune($d, $now);

        $token = (string)($j['lt'] ?? '');
        $tokenBox = $token !== '' ? og_live_token_unpack($C, $token) : null;
        $tokenHash = hash_hmac('sha256', $token, (string)$C['cookie_secret']);
        $expectedToken = og_live_token_expected($C, $ip, $ua, $host, $origin, $pageUri, $challenge, $fpLive, (int)($tokenBox['b'] ?? $bucket));
        $slot = is_array($d['live_tokens'][$tokenSlot] ?? null) ? $d['live_tokens'][$tokenSlot] : [];
        $confirmOk = is_array($tokenBox)
            && !empty($slot['h'])
            && hash_equals((string)$slot['h'], $tokenHash)
            && empty($slot['used'])
            && (int)($slot['exp'] ?? 0) >= $now
            && (string)($tokenBox['t'] ?? '') === $expectedToken
            && (string)($tokenBox['h'] ?? '') === $host
            && (string)($tokenBox['o'] ?? '') === $origin
            && (string)($tokenBox['u'] ?? '') === $pageUri
            && (string)($tokenBox['r'] ?? '') === $challenge
            && (string)($tokenBox['fp'] ?? '') === substr(hash('sha256', $fpLive), 0, 24)
            && (string)($tokenBox['ip'] ?? '') === hash_hmac('sha256', $ip, (string)$C['cookie_secret'])
            && (string)($tokenBox['ua'] ?? '') === hash_hmac('sha256', $ua, (string)$C['cookie_secret'])
            && (int)($tokenBox['exp'] ?? 0) >= $now;

        if (!$confirmOk) {
            $reasons = [];
            if (!is_array($tokenBox)) $reasons[] = 'token_unpack_failed';
            if (empty($slot['h'])) $reasons[] = 'no_slot_hash';
            elseif (!hash_equals((string)$slot['h'], $tokenHash)) $reasons[] = 'slot_hash_mismatch';
            if (!empty($slot['used'])) $reasons[] = 'slot_already_used';
            if ((int)($slot['exp'] ?? 0) < $now) $reasons[] = 'slot_expired';
            if (is_array($tokenBox)) {
                if ((string)($tokenBox['t'] ?? '') !== $expectedToken) $reasons[] = 't_mismatch';
                if ((string)($tokenBox['h'] ?? '') !== $host) $reasons[] = 'host_mismatch';
                if ((string)($tokenBox['o'] ?? '') !== $origin) $reasons[] = 'origin_mismatch';
                if ((string)($tokenBox['u'] ?? '') !== $pageUri) $reasons[] = 'uri_mismatch';
                if ((string)($tokenBox['r'] ?? '') !== $challenge) $reasons[] = 'challenge_mismatch';
                if ((string)($tokenBox['fp'] ?? '') !== substr(hash('sha256', $fpLive), 0, 24)) $reasons[] = 'fp_mismatch';
                if ((string)($tokenBox['ip'] ?? '') !== hash_hmac('sha256', $ip, (string)$C['cookie_secret'])) $reasons[] = 'ip_mismatch';
                if ((string)($tokenBox['ua'] ?? '') !== hash_hmac('sha256', $ua, (string)$C['cookie_secret'])) $reasons[] = 'ua_mismatch';
                if ((int)($tokenBox['exp'] ?? 0) < $now) $reasons[] = 'token_expired';
            }
            og_xlog('error', 'live_confirm', 'confirm_failed', ['reasons' => $reasons, 'host' => $host, 'origin' => $origin, 'uri' => $pageUri, 'fail_count' => (int)($d['live_token_fail'] ?? 0) + 1]);
            $d['live_token_fail'] = min(3, (int)($d['live_token_fail'] ?? 0) + 1);
            $d['live_token_last_fail'] = $now;
            og_save($d, $ipf);
            og_ip_unlock($_confirmLock);
            http_response_code(200);
            echo json_encode(['p' => og_b64url_encode(json_encode(['ok' => false, 'blocked' => true, 'reason' => 'live_token_confirm_failed'], JSON_UNESCAPED_SLASHES))]);
            exit;
        }
        og_xlog('info', 'live_confirm', 'confirm_ok', ['host' => $host, 'uri' => $pageUri, 'split' => !empty($C['live_key_split']), 'has_ak' => null]);

        if (!empty($C['live_hook_one_tab']) && !og_live_tab_id_valid($tabId)) {
            og_save($d, $ipf);
            http_response_code(200);
            echo json_encode(['p' => og_b64url_encode(json_encode(['ok' => false, 'blocked' => true, 'reason' => 'tab_limit'], JSON_UNESCAPED_SLASHES))]);
            exit;
        }
        if (!empty($C['live_hook_one_tab']) && og_live_hook_tab_blocked($d, $C, $hookKey, $tabId, $now)) {
            if ($og_is_origin) {
                // Origin: force-replace stale hook (user refreshed/reopened tab within TTL)
                og_live_hook_prune($d, $now);
                $d['live_hook_tabs'][$hookKey] = ['tab' => $tabId, 'sidh' => '', 'pubk' => '', 'exp' => $now + $hookLeaderTtl];
                og_runtime_log($rtLog, $ip, $uri, 'origin_hook_override_confirm:' . og_log_field($hookKey, 16));
            } else {
                og_try_ban_bad_live_pub($C, $d, $ip, $uri, 'confirm_tab_limit', $rtLog, $now);
                og_save($d, $ipf);
                http_response_code(200);
                echo json_encode(['p' => og_b64url_encode(json_encode(['ok' => false, 'blocked' => true, 'reason' => 'tab_limit'], JSON_UNESCAPED_SLASHES))]);
                exit;
            }
        }

        $d['live_token_used'] = 1;
        $d['live_token_confirmed'] = $now;
        $liveSid = bin2hex(random_bytes(16));
        $sidHash = hash_hmac('sha256', $liveSid, (string)$C['cookie_secret']);
        $minted = og_live_pub_mint($d, $C, $sidHash, $ip, $now);
        $finalMint = og_webhook_mint_sync($d, $C, $minted, $sidHash, $ip, $now, $host, $liveSid, 'mint');
        if ($finalMint === null) {
            unset($d['live_pub_tokens'][$minted['k']]);
            og_save($d, $ipf);
            http_response_code(200);
            echo json_encode(['p' => og_b64url_encode(json_encode(['ok' => false, 'blocked' => true, 'reason' => 'live_webhook_denied'], JSON_UNESCAPED_SLASHES))]);
            exit;
        }
        $livePub = $finalMint['pub'];
        $pubK = $finalMint['k'];
        if (!empty($C['live_hook_one_tab'])) {
            $hookClaim = og_live_hook_claim($d, $C, $hookKey, $tabId, $sidHash, $pubK, $now, $hookLeaderTtl);
            if ($hookClaim === 'tab_limit') {
                unset($d['live_pub_tokens'][$pubK]);
                og_live_pub_detach_all_for_sid($d, $sidHash);
                if (!og_request_is_origin($C, $host)) {
                    og_try_ban_bad_live_pub($C, $d, $ip, $uri, 'mint_tab_limit', $rtLog, $now);
                }
                og_save($d, $ipf);
                http_response_code(200);
                echo json_encode(['p' => og_b64url_encode(json_encode(['ok' => false, 'blocked' => true, 'reason' => 'tab_limit'], JSON_UNESCAPED_SLASHES))]);
                exit;
            }
        }
        $liveSession = [
            'exp' => $now + $ttlBeat,
            'last' => $now,
            'start' => $now,
            'host' => $host,
            'origin' => $origin,
            'uri' => $pageUri,
            'challenge' => $challenge,
            'fp' => substr(hash('sha256', $fpLive), 0, 24),
            'pubk' => $pubK,
            'tab' => $tabId,
            'hook' => $hookKey,
        ];
        $d['live_sessions'][$sidHash] = $liveSession;
        if (count($d['live_sessions']) > 32) {
            $d['live_sessions'] = array_slice($d['live_sessions'], -32, null, true);
        }
        $d['live_session_hash'] = $sidHash;
        $d['live_session_exp'] = $liveSession['exp'];
        $d['live_session_last'] = $now;
        $d['live_session_host'] = $host;
        $d['live_session_origin'] = $origin;
        $d['live_session_uri'] = $pageUri;
        $d['live_session_challenge'] = $challenge;
        $d['live_session_fp'] = $liveSession['fp'];
        $d['human_score'] = max((float)($d['human_score'] ?? 0), 1.0);
        $d['human_last'] = $now;
        $d['human_miss'] = 0;
        if (isset($d['live_tokens'][$tokenSlot])) unset($d['live_tokens'][$tokenSlot]);
        if (empty($d['live_tokens'])) unset($d['live_tokens']);
        unset($d['live_token_hash'], $d['live_token_fail']);
        og_save($d, $ipf);
        og_ip_unlock($_confirmLock);
        setcookie((string)($C['live_token_cookie'] ?? '_oglive'), '', [
            'expires'  => $now - 3600,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => $secure,
        ]);
        $gexp = $finalMint['exp'];
        $sessionKey = og_live_derive_payload_key_b64($C, $host, $challenge);
        if ($sessionKey === null || $sessionKey === '') {
            og_save($d, $ipf);
            http_response_code(200);
            echo json_encode(['p' => og_b64url_encode(json_encode(['ok' => false, 'blocked' => true, 'reason' => 'copy_host_denied'], JSON_UNESCAPED_SLASHES))]);
            exit;
        }
        $splitOn = !empty($C['live_key_split']);
        $hbSecs = 8;
        $assetMasterKeyB64 = og_live_derive_asset_master_key_b64($C, $host);
        $resp = array_merge(
            ['ok' => true, 'c' => true, 'h' => $host, 'o' => $origin, 'u' => $pageUri, 'r' => $challenge, 'k' => $sessionKey, 's' => $liveSid, 'hb' => $hbSecs, 'sexp' => $now + $ttlBeat, 'g' => $livePub, 'gexp' => $gexp],
            $assetMasterKeyB64 !== null ? ['ak' => $assetMasterKeyB64] : [],
            og_live_family_payload($C, $host)
        );
        if ($splitOn) {
            // og_live_derive_payload_key_b64 already enforced og_request_is_origin; safe to emit bootstrap fragment.
            $kfTtl = max(3, min(60, (int)($C['live_kfrag_ttl'] ?? 8)));
            $canonKf = og_canonical_host_cfg($C);
            $masterKf = trim((string)($C['live_payload_key'] ?? ''));
            $resp['split'] = 1;
            $resp['kf0'] = og_b64url_encode(og_derive_kfrag_boot($masterKf, $canonKf, $challenge));
            $resp['kfb'] = 0;
            $resp['kft'] = $kfTtl;
            $resp['hb'] = min($hbSecs, $kfTtl);
        }
        echo json_encode(['p' => og_b64url_encode(json_encode($resp, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))]);
        exit;
    }

    if ($action === 'issue' && !empty($C['live_hook_one_tab'])) {
        if (!og_live_tab_id_valid($tabId)) {
            og_save($d, $ipf);
            http_response_code(200);
            echo json_encode(['p' => og_b64url_encode(json_encode(['ok' => false, 'blocked' => true, 'reason' => 'tab_limit'], JSON_UNESCAPED_SLASHES))]);
            exit;
        }
        if (og_live_hook_tab_blocked($d, $C, $hookKey, $tabId, $now)) {
            if ($og_is_origin) {
                // Origin: pre-claim hook slot for this tab (stale lock from previous session)
                og_live_hook_prune($d, $now);
                $d['live_hook_tabs'][$hookKey] = ['tab' => $tabId, 'sidh' => '', 'pubk' => '', 'exp' => $now + $hookLeaderTtl];
                og_runtime_log($rtLog, $ip, $uri, 'origin_hook_override_issue:' . og_log_field($hookKey, 16));
            } else {
                og_try_ban_bad_live_pub($C, $d, $ip, $uri, 'issue_tab_limit', $rtLog, $now);
                og_save($d, $ipf);
                http_response_code(200);
                echo json_encode(['p' => og_b64url_encode(json_encode(['ok' => false, 'blocked' => true, 'reason' => 'tab_limit'], JSON_UNESCAPED_SLASHES))]);
                exit;
            }
        }
    }

    $plainToken = og_live_token_expected($C, $ip, $ua, $host, $origin, $pageUri, $challenge, $fpLive, $bucket);
    $token = og_live_token_pack($C, [
        'v'   => 2,
        't'   => $plainToken,
        'h'   => $host,
        'o'   => $origin,
        'u'   => $pageUri,
        'r'   => $challenge,
        'fp'  => substr(hash('sha256', $fpLive), 0, 24),
        'ip'  => hash_hmac('sha256', $ip, (string)$C['cookie_secret']),
        'ua'  => hash_hmac('sha256', $ua, (string)$C['cookie_secret']),
        'b'   => $bucket,
        'iat' => $now,
        'exp' => $now + $ttl,
        'n'   => bin2hex(random_bytes(12)),
    ]);
    if ($token === null) {
        http_response_code(503);
        echo json_encode(['p' => og_b64url_encode(json_encode(['ok' => false], JSON_UNESCAPED_SLASHES))]);
        exit;
    }

    // Lock and re-read before writing the slot so concurrent requests don't clobber each other.
    $_issueLock = og_ip_lock($ipf);
    $_dFresh    = is_file($ipf) ? (json_decode(@file_get_contents($ipf), true) ?? []) : [];
    if (is_array($_dFresh['live_tokens'] ?? null)) {
        $d['live_tokens'] = $_dFresh['live_tokens'];
    }

    $d['live_tokens'] = is_array($d['live_tokens'] ?? null) ? $d['live_tokens'] : [];
    foreach ($d['live_tokens'] as $k => $row) {
        if (!is_array($row) || (int)($row['exp'] ?? 0) < $now) unset($d['live_tokens'][$k]);
    }
    if (count($d['live_tokens']) > 12) {
        $d['live_tokens'] = array_slice($d['live_tokens'], -12, null, true);
    }
    $d['live_tokens'][$tokenSlot] = [
        'h' => hash_hmac('sha256', $token, (string)$C['cookie_secret']),
        'exp' => $now + $ttl,
        't' => $now,
    ];
    $d['live_token_hash'] = $d['live_tokens'][$tokenSlot]['h'];
    $d['live_token_exp']  = $now + $ttl;
    $d['live_token_used'] = 0;
    $d['live_token_last'] = $now;
    $d['live_token_host'] = $host;
    $d['live_token_uri']  = $pageUri;
    unset($d['live_token_fail']);
    og_save($d, $ipf);
    og_ip_unlock($_issueLock);

    $resp = array_merge(
        ['ok' => true, 'phase' => 'issue', 't' => $token, 'exp' => $now + $ttl, 'h' => $host, 'o' => $origin, 'u' => $pageUri, 'r' => $challenge],
        og_live_family_payload($C, $host)
    );
    echo json_encode(['p' => og_b64url_encode(json_encode($resp, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))]);
    exit;
}

// ══════════════════════════════════════════════════════════════════
//  БЛОК T — TIMING ANALYSIS (детект ботов по ритму запросов)
// ══════════════════════════════════════════════════════════════════
// Timing: не для origin і не для OG heartbeat ($og_is_og_endpoint вище)
if (!$og_is_origin && !$og_is_og_endpoint && count($d['ts']) >= (int)$C['timing_window']) {
    $recent = array_slice($d['ts'], -(int)$C['timing_window']);
    $intervals = [];
    for ($i = 1; $i < count($recent); $i++) {
        $intervals[] = (float)($recent[$i] - $recent[$i - 1]);
    }
    // Убираем нулевые интервалы (несколько запросов в 1 сек — уже rl)
    $intervals = array_values(array_filter($intervals, static function ($v) { return $v > 0; }));
    if (count($intervals) >= 5) {
        $std = og_stddev($intervals);
        $d['timing_std'] = round($std, 4);
        // Слишком равномерно (std < порога) И много запросов — бот
        if ($std < (float)$C['timing_stddev_min'] && count($d['ts']) >= 15) {
            $d['suspect'] = ($d['suspect'] ?? 0) + 30;
            $d['suspect_last'] = $now;
            $d['timing_strikes'] = ($d['timing_strikes'] ?? 0) + 1;
            og_save($d, $ipf);
            if ($d['timing_strikes'] >= 2) {
                if ($og_browser_shell_candidate) {
                    $d['suspect_reasons'][] = 'timing_soft';
                    og_save($d, $ipf);
                } else {
                    og_mark_block($d, 'atk', $now + (int)$C['timing_block'], 'timing_uniform:std=' . round($std, 4), 'timing_analysis', $rtLog, $ip, $uri);
                    og_save($d, $ipf);
                    $og_fast_block(403);
                }
            }
        }
    }
}

// ══════════════════════════════════════════════════════════════════
//  БЛОК H — HEADER FINGERPRINT (порядок и набор заголовков)
// ══════════════════════════════════════════════════════════════════
// Браузеры ВСЕГДА шлют Accept, Accept-Language, Accept-Encoding
// Большинство HTTP-библиотек не шлют их, или шлют в неправильном порядке
//
// КРИТИЧНО: для origin запросів (наш JS у браузері з canonical hostа) header-fingerprint
// дає масу false positive:
//   - fetch() XHR без явного Accept-header → 'Accept: */*' (+35)
//   - fetch() часто без Accept-Language (+20)
//   - в сумі один наш XHR дає 55 очок → 2 XHR → suspect=100 → постійне очищення банів.
// Origin XHR — це наш own JS, fingerprint-чек тут ненадійний, тому пропускаємо.

$suspect = (int)($d['suspect'] ?? 0);
$suspect_on_entry = $suspect; // сохраняем значение ДО текущего запроса

if (!$og_is_origin) {

// 1. Отсутствие обязательных заголовков браузера
$has_accept   = !empty($_SERVER['HTTP_ACCEPT']);
$has_ac_lang  = !empty($_SERVER['HTTP_ACCEPT_LANGUAGE']);
$has_ac_enc   = !empty($_SERVER['HTTP_ACCEPT_ENCODING']);

if (!$has_accept)  { $suspect += 25; $d['suspect_reasons'][] = 'no_accept'; }
if (!$has_ac_lang) { $suspect += 20; $d['suspect_reasons'][] = 'no_accept_language'; }
if (!$has_ac_enc)  { $suspect += 15; $d['suspect_reasons'][] = 'no_accept_encoding'; }

// 2. Accept: */* без дополнительных типов — признак curl/python
$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
if ($accept === '*/*') { $suspect += 35; $d['suspect_reasons'][] = 'accept_star'; }

// 3. Accept-Language не содержит реального языка (только q-значения или пустой)
if ($has_ac_lang) {
    $al = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    if (!preg_match('/[a-z]{2}(-[A-Z]{2})?/i', $al)) {
        $suspect += 10;
        $d['suspect_reasons'][] = 'malformed_accept_language';
    }
}

// 4. HTTP_CONNECTION анализ: браузеры шлют keep-alive, боты — close или ничего
$conn = strtolower($_SERVER['HTTP_CONNECTION'] ?? '');
if ($conn === 'close') { $suspect += 10; $d['suspect_reasons'][] = 'connection_close'; }

// 5. Заголовок DNT без Sec-Fetch (старый паттерн автоматизации)
$has_dnt        = isset($_SERVER['HTTP_DNT']);
$has_sec_fetch  = isset($_SERVER['HTTP_SEC_FETCH_SITE']) || isset($_SERVER['HTTP_SEC_FETCH_MODE']);
if ($has_dnt && !$has_sec_fetch && $has_ac_lang) {
    // Подозрительно но не критично
    $suspect += 5;
    $d['suspect_reasons'][] = 'dnt_without_sec_fetch';
}

// 6. Sec-Ch-Ua без User-Agent совпадения — боты часто спуфят UA
if (!empty($_SERVER['HTTP_SEC_CH_UA'])) {
    $ch_ua = strtolower($_SERVER['HTTP_SEC_CH_UA']);
    // Если CH-UA говорит Chrome, а UA говорит Firefox — подделка
    $ua_is_chrome  = og_contains($ua_l, 'chrome') && !og_contains($ua_l, 'edg');
    $ua_is_firefox = og_contains($ua_l, 'firefox');
    $ch_is_chrome  = og_contains($ch_ua, 'chromium') || og_contains($ch_ua, 'chrome') || og_contains($ch_ua, 'google chrome');
    if ($ua_is_firefox && $ch_is_chrome) {
        $suspect += 35;
        $d['suspect_reasons'][] = 'ua_cha_mismatch';
    }
}

// 6b. Fetch Metadata: Chromium / Edge / iOS Chrome на GET к HTML без расширений — всегда шлют Sec-Fetch-*
// HTTP-клиенты с поддельным UA и часть headless оставляют поля пустыми (копии — строго; origin — не баним)
if (!empty($C['sec_fetch_strict']) && $method === 'GET' && !$og_is_origin) {
    $pathOnly = (string)(parse_url($uri, PHP_URL_PATH) ?? $uri);
    if (!preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff2?|ttf|eot|mp4|webm|webp|pdf|map|json)(\?|$)/i', $pathOnly)) {
        $maj = 0;
        if (preg_match('/Chrome\/(\d+)/i', $ua, $chv) && stripos($ua, 'EdgA') === false && stripos($ua, 'EdgiOS') === false) {
            $maj = max($maj, (int)$chv[1]);
        }
        if (preg_match('/CriOS\/(\d+)/i', $ua, $cri)) {
            $maj = max($maj, (int)$cri[1]);
        }
        if (preg_match('/Edg\/(\d+)/i', $ua, $edg)) {
            $maj = max($maj, (int)$edg[1]);
        }
        if ($maj >= 91) {
            $sf_s = $_SERVER['HTTP_SEC_FETCH_SITE'] ?? '';
            $sf_m = $_SERVER['HTTP_SEC_FETCH_MODE'] ?? '';
            $sf_d = $_SERVER['HTTP_SEC_FETCH_DEST'] ?? '';
            if ($sf_s === '' && $sf_m === '' && $sf_d === '') {
                $suspect += 30;
                $d['suspect_reasons'][] = 'chromium_no_sec_fetch';
            }
        }
    }
}

// 7. HTTP_CACHE_CONTROL: браузеры шлют no-cache или max-age, боты — ничего
// Не блокируем, только добавляем к suspect если других сигналов много

// 8. Слишком большое или слишком маленькое количество заголовков
if (function_exists('getallheaders')) {
    $og_hdrs = getallheaders();
    if (is_array($og_hdrs)) {
        $hcount = count($og_hdrs);
        if ($hcount < 3)  { $suspect += 30; $d['suspect_reasons'][] = 'too_few_headers'; }
        if ($hcount > 45) { $suspect += 15; $d['suspect_reasons'][] = 'too_many_headers'; }
    }
}

// 9. Детект эмуляции мобильного устройства парсером
// Парсеры ставят iPhone/Android UA чтобы обойти защиту, но не могут подделать весь профиль
$ua_claims_mobile = (bool)preg_match('/iPhone|iPad|Android|Mobile|mobi/i', $ua);
if ($ua_claims_mobile && !$is_static) {

    // 9a. Мобильный UA но нет Sec-CH-UA-Mobile (Chrome 90+ всегда шлёт на мобиле)
    $has_ch_mobile = isset($_SERVER['HTTP_SEC_CH_UA_MOBILE']);
    $ua_claims_chrome_mob = (bool)preg_match('/Chrome\/[7-9]\d|Chrome\/1\d{2}/i', $ua);
    if ($ua_claims_chrome_mob && !$has_ch_mobile && $has_sec_fetch) {
        // Современный мобильный Chrome без CH-UA-Mobile = эмуляция DevTools или скрипт
        $suspect += 30;
        $d['suspect_reasons'][] = 'mob_no_ch_ua_mobile';
    }

    // 9b. Мобильный UA но Accept не содержит image/webp (все мобильные браузеры поддерживают)
    $accept_hdr = $_SERVER['HTTP_ACCEPT'] ?? '';
    $ua_claims_safari_mob = (bool)preg_match('/iPhone|iPad/i', $ua);
    if ($ua_claims_safari_mob && strpos($accept_hdr, 'image/webp') === false && $accept_hdr !== '' && $accept_hdr !== '*/*') {
        $suspect += 20;
        $d['suspect_reasons'][] = 'mob_no_webp_accept';
    }

    // 9c. Мобильный UA но есть заголовки только десктопного Chrome (Sec-CH-UA без ?1 платформы)
    if (isset($_SERVER['HTTP_SEC_CH_UA_PLATFORM'])) {
        $platform = strtolower(trim($_SERVER['HTTP_SEC_CH_UA_PLATFORM'], '"'));
        $ua_claims_android = (bool)preg_match('/Android/i', $ua);
        $ua_claims_iphone  = (bool)preg_match('/iPhone|iPad/i', $ua);
        // Платформа десктопная, а UA мобильный
        if (($ua_claims_android || $ua_claims_iphone) && in_array($platform, ['windows', 'linux', 'macos', 'chromeos'])) {
            $suspect += 40;
            $d['suspect_reasons'][] = 'mob_platform_mismatch';
        }
    }

    // 9d. Android UA без Sec-CH-UA-Platform вообще — либо очень старый браузер либо curl
    if (preg_match('/Android/i', $ua) && !isset($_SERVER['HTTP_SEC_CH_UA_PLATFORM']) && $has_sec_fetch) {
        $suspect += 15;
        $d['suspect_reasons'][] = 'android_no_platform_hint';
    }

    // 9e. Мобильный UA + Accept-Encoding без br (Brotli) — curl/python не поддерживают br по умолчанию
    $ac_enc = strtolower($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '');
    if ($ua_claims_mobile && $has_ac_enc && strpos($ac_enc, 'br') === false) {
        $suspect += 15;
        $d['suspect_reasons'][] = 'mob_no_brotli';
    }
}

} // end if (!$og_is_origin) — header fingerprint скіп для legit origin

$d['suspect'] = $suspect;
if ($suspect > 0) $d['suspect_last'] = $now;

// ── Мгновенная блокировка по очкам текущего запроса ──────────────
// Если за ОДИН запрос набрано >= instant_block_score новых очков — блокируем
// сразу, не ждём следующего запроса. Это закрывает one-shot парсеры.
$suspect_gained = $suspect - $suspect_on_entry;
if (!$og_is_origin && !$og_browser_shell_candidate && $suspect_gained >= (int)$C['instant_block_score'] && !$og_soft_only_reasons($d)) {
    og_mark_block($d, 'atk', $now + (int)$C['atk_block'], 'instant_suspect:' . $suspect_gained, 'header_fingerprint', $rtLog, $ip, $uri);
    $d['last_violation'] = $now;
    og_save($d, $ipf);
    $block('instant_suspect:' . $suspect_gained, 403);
}
// Также: если суммарный suspect >= suspect_block — блокируем здесь же (раньше это было позже)
if (!$og_is_origin && !$og_browser_shell_candidate && $suspect >= (int)$C['suspect_block'] && !$og_soft_only_reasons($d)) {
    og_mark_block($d, 'atk', $now + (int)$C['atk_block'], 'suspect_score_exceeded_early', 'header_fingerprint', $rtLog, $ip, $uri);
    $d['last_violation'] = $now;
    og_save($d, $ipf);
    $block('suspect_score_exceeded_early', 403);
}

// ══════════════════════════════════════════════════════════════════
//  БЛОК 1 — ПОСТОЯННАЯ / ВРЕМЕННАЯ БЛОКИРОВКА
// ══════════════════════════════════════════════════════════════════
if (!empty($d['atk_block']) && $now < $d['atk_block']) $block('permanently_blocked');
if (!empty($d['rl_block'])  && $now < $d['rl_block'])  $block('rate_blocked', 429);

// ══════════════════════════════════════════════════════════════════
//  БЛОК 2 — USER AGENT И КРАУЛЕРЫ
// ══════════════════════════════════════════════════════════════════
if (empty(trim($ua))) { $suspect += 40; $d['suspect_reasons'][] = 'no_ua'; }
if (strlen($ua) < 20) { $suspect += 30; $d['suspect_reasons'][] = 'ua_too_short'; }

$bad_ua = [
    'ahrefsbot','semrushbot','dotbot','mj12bot','blexbot','petalbot',
    'yandexbot','baiduspider','sogou','exabot','facebot','ia_archiver',
    'archive.org_bot','httrack','webcopier','teleport','webcollage',
    'screaming frog','googlebot','googlebot-mobile','googlebot-image',
    'slurp','msn','bing','inktomi','looksmart','altavista','nutch','jeevesbot',
    'technoratibot','topicblast','seeker','wikiwix','rogerbot','purebot',
    'speedy spider','ccbot','panscient','yodaobot','naver','zermelo','searchbot',
    'moreover','dlvr.it','outbrain','pinterest','feedfetcher','summify',
    'bot','spider','crawl','scrape','fetch','link','check','monitor',
    'python-requests','python-urllib','python/','curl/','wget/','libwww-perl',
    'scrapy','go-http-client','java/','okhttp','axios','node-fetch','undici',
    'got/','superagent','aiohttp','httpx','pycurl','rest-client','requests/library',
    'masscan','nikto','sqlmap','nmap','zgrab','nuclei','dirbuster','gobuster',
    'ffuf','wfuzz','burpsuite','zaproxy','metasploit','havij','acunetix',
    'openvas','nessus','qualys','rapid7','appscan','veracode',
    'headlesschrome','phantomjs','selenium','webdriver','jakarta',
    'puppeteer','playwright','cypress','nightmarejs','testcafe','watir',
    'protractor','nightmare','capybara','webdriverio',
    'gptbot','chatgpt-user','anthropic-ai','claudebot','perplexitybot',
    'bytespider','imagesiftbot','omgili','dataforseo','amazonbot',
    'cohere-ai','google-extended','applebot-extended','youbot','llama-gpt',
    'bingbot','bingmobilebot','bingpreview','msnbot-media',
    'go/','lwp-trivial','twitterbot','linkedinbot','slackbot','discordbot',
    'duckduckbot','teoma','comodo','appscandynasty','whatsupgold',
    '360spider','siteexplorer','sitecheck','linkchecker','urlscan',
    'monitis','statuspage','pingdom','uptimerobot','alertsite',
];
$ua_check = preg_replace('/\/[\d\.\-\w]+/', '', $ua_l);
foreach ($bad_ua as $p) {
    if (og_contains($ua_l, $p) || og_contains($ua_check, $p)) {
        $block('bad_ua:' . $p);
    }
}

// Дополнительные признаки краулеров (добавляем к suspect)
// КРИТИЧНО: пропускаємо для origin — fetch() XHR часто без Accept/Accept-Lang,
// це не означає що користувач бот.
if (!$og_is_origin && empty($_SERVER['HTTP_ACCEPT'])) { $d['suspect'] = ($d['suspect'] ?? 0) + 20; }
if (!$og_is_origin
    && strpos($ua_l, 'firefox') === false && strpos($ua_l, 'chrome') === false
    && strpos($ua_l, 'safari') === false  && strpos($ua_l, 'edge')   === false) {
    if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) { $d['suspect'] = ($d['suspect'] ?? 0) + 15; }
    if (empty($_SERVER['HTTP_ACCEPT_ENCODING'])) { $d['suspect'] = ($d['suspect'] ?? 0) + 15; }
}
// Mobile UA без Accept-Language — у origin XHR fetch це норма (fetch не шле Accept-Lang).
if (!$og_is_origin
    && (stripos($ua, 'mobile') !== false || stripos($ua, 'android') !== false)) {
    if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE']) && empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        $block('fake_mobile_ua');
    }
}
// Очень "грязный" UA — все ещё ban (це 100% бот навіть якщо origin).
if (substr_count($ua, '.') > 25 || substr_count($ua, '/') > 10) {
    $block('suspicious_ua_format');
}

// ══════════════════════════════════════════════════════════════════
//  БЛОК S — SUSPECT SCORE ПРОВЕРКА И CHALLENGE
// ══════════════════════════════════════════════════════════════════
$d['suspect'] = min(100, (int)($d['suspect'] ?? 0));

// Если suspect достиг порога challenge — математика + одноразовый nonce + PoW (снижает headless без полноценного JS)
// КРИТИЧНО: для origin запросів НЕ показуємо challenge — XHR/fetch не може його обробити,
// а звичайний browser XHR має `Accept: */*` (без Accept-Language), що тригерить suspect.
// На canonical hoste довіряємо origin-checked запитам.
if (!$og_is_origin && !$og_browser_shell_candidate && $d['suspect'] >= (int)$C['challenge_on_suspect'] && $d['suspect'] < (int)$C['suspect_block']) {
    $ch_key    = '_ogc';
    $ch_answer = $_COOKIE[$ch_key] ?? '';
    $ch_secret = $C['cookie_secret'];
    $ch_seed   = date('YmdH') . $iph;  // меняется каждый час
    $a1        = (crc32(substr($ch_seed, 0, 4)) % 12) + 2;
    $a2        = (crc32(substr($ch_seed, 4, 4)) % 10) + 2;
    $expected  = substr(hash_hmac('sha256', (string)($a1 * $a2), $ch_secret), 0, 12);

    if ($ch_answer !== $expected) {
        $pow_ttl = (int)($C['challenge_nonce_ttl'] ?? 900);
        if (empty($d['pow_nonce']) || ($now - (int)($d['pow_nonce_t'] ?? 0)) > $pow_ttl) {
            $d['pow_nonce']   = bin2hex(random_bytes(16));
            $d['pow_nonce_t'] = $now;
            og_save($d, $ipf);
        }
        $powN       = (int)($C['challenge_pow_hex'] ?? 0);
        $nonce_h    = htmlspecialchars((string)$d['pow_nonce'], ENT_QUOTES, 'UTF-8');
        $hp_field   = (string)($C['challenge_hp_field'] ?? '_og_hp_company');
        $hp_name    = htmlspecialchars($hp_field, ENT_QUOTES, 'UTF-8');
        $question   = $a1 . ' × ' . $a2 . ' = ?';
        $pow_script = '';
        if ($powN > 0) {
            $pow_script = '<script>(function(){var form=document.getElementById("_og_chf");if(!form||!window.crypto||!crypto.subtle)return;var pc=form.querySelector("input[name=\"_ogpowc\"]");var powN=pc?parseInt(pc.value,10)||0:0;if(powN<=0)return;var prefix=Array(powN+1).join("0");form.addEventListener("submit",function(ev){var w=form.querySelector("input[name=\"_ogw\"]"),n=form.querySelector("input[name=\"_ogn\"]");if(!w||!n||w.value)return;ev.preventDefault();var nc=n.value,i=0;function b2h(b){return Array.prototype.map.call(new Uint8Array(b),function(x){return("0"+x.toString(16)).slice(-2);}).join("");}function step(){crypto.subtle.digest("SHA-256",(new TextEncoder).encode(nc+"|"+i)).then(function(d){var hx=b2h(d);if(hx.substring(0,powN)===prefix){w.value=String(i);form.submit();return;}i++;if(i>14e6)return;i%1024===0?requestAnimationFrame(step):step();});}step();});})();</script>';
        }
        $ch_html = '<form id="_og_chf" class="challenge-box" method="POST" action="' . htmlspecialchars($uri, ENT_QUOTES) . '">'
            . '<label>Докажите, что вы человек. Решите пример:</label>'
            . '<strong style="font-size:22px;color:#fff;display:block;margin:8px 0">' . $question . '</strong>'
            . '<input type="number" name="_ogans" placeholder="Ответ..." required autofocus>'
            . '<input type="hidden" name="_oguri" value="' . htmlspecialchars($uri, ENT_QUOTES) . '">'
            . '<input type="hidden" name="_ogn" value="' . $nonce_h . '">'
            . '<input type="hidden" name="_ogw" value="">'
            . '<input type="hidden" name="_ogpowc" value="' . $powN . '">'
            . '<input type="text" name="' . $hp_name . '" value="" autocomplete="off" tabindex="-1" '
            . 'style="position:absolute;left:-8000px;height:1px;width:1px;opacity:0" aria-hidden="true">'
            . '<button type="submit" class="btn btn-primary" style="width:100%;margin-top:12px">Подтвердить →</button>'
            . '</form>' . $pow_script;
        $block('challenge_required', 403, $ch_html);
    }
}

// Обработка POST-ответа на challenge
if ($method === 'POST' && isset($_POST['_ogans'], $_POST['_oguri'])) {
    $hp_field = (string)($C['challenge_hp_field'] ?? '_og_hp_company');
    if ($hp_field !== '' && trim((string)($_POST[$hp_field] ?? '')) !== '') {
        $d['suspect'] = min(100, (int)($d['suspect'] ?? 0) + 45);
        $d['suspect_reasons'][] = 'challenge_honeypot';
        $d['last_violation'] = $now;
        og_save($d, $ipf);
        $block('challenge_bot_trap', 403);
    }

    $ch_secret = $C['cookie_secret'];
    $ch_seed   = date('YmdH') . $iph;
    $a1        = (crc32(substr($ch_seed, 0, 4)) % 12) + 2;
    $a2        = (crc32(substr($ch_seed, 4, 4)) % 10) + 2;
    $expected  = substr(hash_hmac('sha256', (string)($a1 * $a2), $ch_secret), 0, 12);
    $given     = (int)$_POST['_ogans'];
    $ok_math   = ($given === ($a1 * $a2));

    $powN = (int)($C['challenge_pow_hex'] ?? 0);
    $pow_ok = true;
    if ($powN > 0 && $ok_math) {
        $pow_ok = false;
        $wn     = trim((string)($_POST['_ogw'] ?? ''));
        $nnc    = (string)($_POST['_ogn'] ?? '');
        $ttl    = (int)($C['challenge_nonce_ttl'] ?? 900);
        if ($nnc !== '' && $wn !== '' && preg_match('/^\d{1,15}$/', $wn)
            && isset($d['pow_nonce']) && hash_equals((string)$d['pow_nonce'], $nnc)
            && ($now - (int)($d['pow_nonce_t'] ?? 0)) <= $ttl) {
            $hx = hash('sha256', $nnc . '|' . $wn);
            $pow_ok = (strncmp($hx, str_repeat('0', $powN), $powN) === 0);
        }
    }

    if ($ok_math && $pow_ok) {
        unset($d['pow_nonce'], $d['pow_nonce_t']);
        $d['suspect'] = max(0, (int)($d['suspect'] ?? 0) - 40);
        $d['challenge_passed'] = $now;
        og_save($d, $ipf);
        setcookie('_ogc', $expected, ['expires' => $now + 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
        $redir = filter_var((string)$_POST['_oguri'], FILTER_VALIDATE_URL) === false ? '/' : (string)$_POST['_oguri'];
        $parsed = parse_url($redir);
        if (!empty($parsed['host']) && strcasecmp((string)$parsed['host'], (string)($_SERVER['HTTP_HOST'] ?? '')) !== 0) {
            $redir = '/';
        }
        header('Location: ' . $redir, true, 302);
        exit;
    }
    $d['suspect'] = min(100, (int)($d['suspect'] ?? 0) + 20);
    $d['last_violation'] = $now;
    if ($ok_math && !$pow_ok) {
        $d['suspect'] = min(100, (int)($d['suspect'] ?? 0) + 15);
        $d['suspect_reasons'][] = 'challenge_pow_fail';
    }
    og_save($d, $ipf);
}

// Если suspect >= блока — перманентный бан (origin + browser shell — только soft, без atk)
if (!$og_is_origin && !$og_browser_shell_candidate && (int)($d['suspect'] ?? 0) >= (int)$C['suspect_block'] && !$og_soft_only_reasons($d)) {
    og_mark_block($d, 'atk', $now + (int)$C['atk_block'], 'suspect_score_exceeded', 'suspect_score', $rtLog, $ip, $uri);
    $d['last_violation'] = $now;
    if (($d['strikes_total'] ?? 0) >= (int)$C['perm_ban_after']) {
        og_perm_ban_add($C['perm_ban_file'], $ip, 'suspect_score_exceeded', $rtLog, $uri, 'suspect_score');
        $d['perm_banned'] = 1;
    }
    og_save($d, $ipf);
    $block('suspect_score_exceeded', 403);
}

// ══════════════════════════════════════════════════════════════════
//  БЛОК 4 — TOR EXIT NODES
// ══════════════════════════════════════════════════════════════════
$is_private = filter_var($ip, FILTER_VALIDATE_IP,
    FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;

if (!$is_private) {
    $tor_list = $C['tor_list'];
    if (!is_file($tor_list) || (time() - filemtime($tor_list)) > $C['tor_ttl']) {
        $raw = og_http_get('https://check.torproject.org/torbulkexitlist');
        if ($raw) @file_put_contents($tor_list, $raw);
    }
    if (is_file($tor_list)) {
        $exits = file($tor_list, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        if (in_array($ip, $exits)) {
            if ($og_browser_shell_candidate) {
                $d['suspect'] = min(100, (int)($d['suspect'] ?? 0) + 20);
                $d['suspect_reasons'][] = 'tor_exit_soft';
                $d['suspect_last'] = $now;
                og_save($d, $ipf);
            } else {
                og_mark_block($d, 'atk', $now + (int)$C['atk_block'], 'tor_exit_node', 'tor_exit_node', $rtLog, $ip, $uri);
                og_save($d, $ipf);
                $block('tor_exit_node');
            }
        }
    }
}

// ══════════════════════════════════════════════════════════════════
//  БЛОК 5 — DATACENTER / CLOUD IP
// ══════════════════════════════════════════════════════════════════
if (!$is_private) {
    foreach ($C['dc_cidrs'] as $cidr) {
        if (og_ip_in_cidr($ip, $cidr)) {
            if ($og_browser_shell_candidate || $og_is_og_endpoint || $isDirectBeacon || $isDirectLiveToken) {
                $d['suspect'] = min(100, (int)($d['suspect'] ?? 0) + 15);
                $d['suspect_reasons'][] = 'datacenter_ip_soft';
                $d['suspect_last'] = $now;
                og_save($d, $ipf);
                break;
            }
            og_mark_block($d, 'atk', $now + (int)$C['atk_block'], 'datacenter_ip', 'datacenter_ip', $rtLog, $ip, $uri);
            og_save($d, $ipf);
            $block('datacenter_ip');
        }
    }
}

// ══════════════════════════════════════════════════════════════════
//  БЛОК 6 — IP REPUTATION (AbuseIPDB)
// ══════════════════════════════════════════════════════════════════
if (!empty($C['abuseipdb_key']) && !$is_private) {
    $abuse_cache = $C['dir'] . '/abuse_' . $iph . '.json';
    $abuse_data  = null;
    if (is_file($abuse_cache) && (time() - filemtime($abuse_cache)) < 3600) {
        $abuse_data = json_decode(file_get_contents($abuse_cache), true);
    }
    if ($abuse_data === null) {
        $raw = og_http_get(
            'https://api.abuseipdb.com/api/v2/check?ipAddress=' . urlencode($ip) . '&maxAgeInDays=30',
            ['Key: ' . $C['abuseipdb_key'], 'Accept: application/json']
        );
        if ($raw) { $abuse_data = json_decode($raw, true); @file_put_contents($abuse_cache, $raw); }
    }
    $abuse_score = $abuse_data['data']['abuseConfidenceScore'] ?? 0;
    if ($abuse_score >= $C['abuseipdb_min']) {
        if ($og_browser_shell_candidate) {
            $d['suspect'] = min(100, (int)($d['suspect'] ?? 0) + 20);
            $d['suspect_reasons'][] = 'abuseipdb_score_soft_' . $abuse_score;
            $d['suspect_last'] = $now;
            og_save($d, $ipf);
        } else {
        og_mark_block($d, 'atk', $now + (int)$C['atk_block'], 'abuseipdb_score_' . $abuse_score, 'abuseipdb', $rtLog, $ip, $uri);
        og_save($d, $ipf);
        $block('abuseipdb_score_' . $abuse_score);
        }
    }
}

// ══════════════════════════════════════════════════════════════════
//  БЛОК 7 — GEOIP
// ══════════════════════════════════════════════════════════════════
if (!empty($C['geo_block']) && !$is_private && is_file($C['geo_db'])) {
    $geo_cache = $C['dir'] . '/geo_' . $iph . '.txt';
    $country   = '';
    if (is_file($geo_cache) && (time() - filemtime($geo_cache)) < 86400) {
        $country = trim(file_get_contents($geo_cache));
    } elseif (class_exists('\MaxMind\Db\Reader')) {
        try {
            $reader  = new \MaxMind\Db\Reader($C['geo_db']);
            $record  = $reader->get($ip);
            $country = $record['country']['iso_code'] ?? '';
            @file_put_contents($geo_cache, $country);
            $reader->close();
        } catch (\Exception $e) {}
    }
    if ($country && in_array($country, $C['geo_block'])) {
        if ($og_browser_shell_candidate) {
            $d['suspect'] = min(100, (int)($d['suspect'] ?? 0) + 10);
            $d['suspect_reasons'][] = 'geo_block_soft:' . $country;
            $d['suspect_last'] = $now;
            og_save($d, $ipf);
        } else {
        og_mark_block($d, 'atk', $now + (int)$C['atk_block'], 'geo_block:' . $country, 'geo_block', $rtLog, $ip, $uri);
        og_save($d, $ipf);
        $block('geo_block:' . $country);
        }
    }
}

// ══════════════════════════════════════════════════════════════════
//  БЛОК 7b — PROXY ROTATION DETECTION (ротация IP / прокси-пулы)
// ══════════════════════════════════════════════════════════════════
// Один и тот же browser-fingerprint (UA+lang+enc) приходит с разных IP —
// признак прокси-пула. Храним глобальный файл fp→[ip, timestamp].
if (!$is_private && !empty($ua)) {
    // Лёгкий fingerprint: UA + Accept-Language + Accept-Encoding
    $bfp = substr(md5(
        ($ua) . '|' .
        ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '') . '|' .
        ($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '') . '|' .
        ($_SERVER['HTTP_SEC_CH_UA'] ?? '')
    ), 0, 16);

    $fpFile = $C['dir'] . '/bfp_' . $bfp . '.json';
    $fpData = is_file($fpFile) ? (json_decode(@file_get_contents($fpFile), true) ?? []) : [];
    $fpData['ips'] = $fpData['ips'] ?? [];

    // Удаляем IP старше 10 минут
    $cutoff = $now - 600;
    $fpData['ips'] = array_filter($fpData['ips'], static function ($e) use ($cutoff) { return ($e['t'] ?? 0) >= $cutoff; });

    // Добавляем текущий IP если его ещё нет
    $knownIps = array_column($fpData['ips'], 'ip');
    if (!in_array($ip, $knownIps, true)) {
        $fpData['ips'][] = ['ip' => $ip, 't' => $now];
    }

    // Если один fingerprint использовался с 4+ разных IP за 10 минут — ротация прокси
    if (count($fpData['ips']) >= 4) {
        $d['suspect'] = min(100, ($d['suspect'] ?? 0) + 40);
        $d['proxy_rotation'] = $now;
        og_save($d, $ipf);
        // При высоком suspect или повторной ротации — блок
        if (($d['suspect'] ?? 0) >= (int)$C['suspect_block']) {
            og_perm_ban_add($C['perm_ban_file'], $ip, 'proxy_rotation_suspect', $rtLog, $uri, 'proxy_rotation');
            $block('proxy_rotation_suspect');
        }
    }

    // Сохраняем fp-файл (ограничиваем до 20 записей)
    $fpData['ips'] = array_values(array_slice($fpData['ips'], -20));
    @file_put_contents($fpFile, json_encode($fpData), LOCK_EX);

    // Дополнительно: если IP меняется чаще чем раз в 30 секунд у одного сессионного fp — бот
    if (!empty($d['last_bfp']) && $d['last_bfp'] === $bfp && !empty($d['last_bfp_ip'])
        && $d['last_bfp_ip'] !== $ip && ($now - ($d['last_bfp_t'] ?? 0)) < 30) {
        $d['suspect'] = min(100, ($d['suspect'] ?? 0) + 20);
        og_save($d, $ipf);
        if (($d['suspect'] ?? 0) >= (int)$C['suspect_block']) {
            $block('rapid_ip_rotation');
        }
    }
    $d['last_bfp']    = $bfp;
    $d['last_bfp_ip'] = $ip;
    $d['last_bfp_t']  = $now;
}

// ══════════════════════════════════════════════════════════════════
$ck_name   = $C['cookie_name'];
$ua_ck     = substr(hash('sha256', $ua), 0, 10);
$ck_expect = substr(hash_hmac('sha256', $ip . '|' . $ua_ck . '|' . date('YmdH'), $C['cookie_secret']), 0, 16);

$needs_challenge = in_array($method, ['GET', 'HEAD'])
    && empty($_SERVER['HTTP_X_REQUESTED_WITH'])
    && !og_contains($uri, '?')
    && !preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|mp4|webp)(\?|$)/i', $uri);

if ($needs_challenge && ($_COOKIE[$ck_name] ?? '') !== $ck_expect) {
    setcookie($ck_name, $ck_expect, [
        'expires'  => $now + 7200,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    header('Location: ' . $uri, true, 302);
    exit;
}

// ── Mini-honeypot до ленда: приманки + одноразовый nonce → cookie _ogpf ──
$pf_name   = (string)($C['preflight_cookie'] ?? '_ogpf');
$pf_expect = substr(hash_hmac('sha256', $ip . '|' . $ua_ck . '|ogpf|' . date('YmdH'), $C['cookie_secret']), 0, 20);
if (!empty($C['preflight_on']) && !$og_is_origin && og_preflight_candidate($C, $method, $uri) && ($_COOKIE[$pf_name] ?? '') !== $pf_expect) {
    $pf_ttl = (int)($C['preflight_nonce_ttl'] ?? 600);
    if (empty($d['pf_nonce']) || empty($d['pf_nonce_t']) || ($now - (int)$d['pf_nonce_t']) > $pf_ttl) {
        $d['pf_nonce']   = bin2hex(random_bytes(16));
        $d['pf_nonce_t'] = $now;
        og_save($d, $ipf);
    }
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('X-Robots-Tag: noindex, nofollow');
    header('Content-Type: text/html; charset=utf-8');
    echo og_preflight_interstitial($uri, (string)$d['pf_nonce']);
    exit;
}

// ══════════════════════════════════════════════════════════════════
//  БЛОК 9 — SESSION INTEGRITY (цепочка переходов)
// ══════════════════════════════════════════════════════════════════
// Браузер переходит по страницам через ссылки — Referer меняется плавно.
// Парсер прыгает по случайным URL без логичной цепочки.
if ($C['session_check'] && $method === 'GET') {
    $ref         = $_SERVER['HTTP_REFERER'] ?? '';
    $ref_host    = parse_url($ref, PHP_URL_HOST) ?? '';
    $own_host    = $_SERVER['HTTP_HOST'] ?? '';
    $is_internal = ($ref_host === $own_host);

    // Считаем глубину URL (кол-во сегментов пути)
    $url_depth = substr_count(parse_url($uri, PHP_URL_PATH) ?? '/', '/');
    $prev_depth= (int)($d['prev_url_depth'] ?? 0);

    // Прыжок глубины > N без internal Referer — признак парсера
    if (!$is_internal && $url_depth > 1) {
        $jump = abs($url_depth - $prev_depth);
        if ($jump > (int)$C['session_max_url_jump'] && empty($d['challenge_passed'])) {
            $d['suspect'] = min(100, (int)($d['suspect'] ?? 0) + 15);
            $d['suspect_reasons'][] = 'url_depth_jump:' . $jump;
        }
    }
    $d['prev_url_depth'] = $url_depth;

    // Подозрение: много уникальных хостов в Referer (фейк)
    if ($ref_host && $ref_host !== $own_host) {
        $d['ext_refs'] = $d['ext_refs'] ?? [];
        $d['ext_refs'][$ref_host] = ($d['ext_refs'][$ref_host] ?? 0) + 1;
        if (count($d['ext_refs']) > 5) {
            $d['suspect'] = min(100, (int)($d['suspect'] ?? 0) + 20);
            $d['suspect_reasons'][] = 'many_external_refs';
        }
    }
}

// ══════════════════════════════════════════════════════════════════
//  БЛОК 9.5 — ПОВЕДЕНЧЕСКИЙ АНАЛИЗ КРАУЛЕРОВ
// ══════════════════════════════════════════════════════════════════
$ua_l_normalized = preg_replace('/\/[\d\.\-\w]+/', '', $ua_l);
$is_known_crawler = false;
$crawler_patterns = ['ahrefs'=>'ahrefsbot','semrush'=>'semrushbot','mj12'=>'mj12bot','bingbot'=>'bingbot','yandex'=>'yandexbot','googlebot'=>'googlebot'];
foreach ($crawler_patterns as $type => $pattern) {
    if (strpos($ua_l_normalized, $pattern) !== false) { $is_known_crawler = true; break; }
}
if ($is_known_crawler) {
    $d['crawler_seq'] = $d['crawler_seq'] ?? [];
    $file_ext = pathinfo($uri, PATHINFO_EXTENSION) ?: 'html';
    $d['crawler_seq'][] = $file_ext;
    $d['crawler_seq']   = array_slice($d['crawler_seq'], -20);
    if (count($d['crawler_seq']) >= 10) {
        $last_10 = array_slice($d['crawler_seq'], -10);
        if (count(array_unique($last_10)) <= 2) {
            og_mark_block($d, 'atk', $now + 86400, 'crawler_scanning_pattern', 'crawler_behavior', $rtLog, $ip, $uri);
            og_save($d, $ipf);
            $block('crawler_scanning_pattern');
        }
    }
}
if (!empty($_SERVER['HTTP_REFERER'])) {
    $refL = strtolower($_SERVER['HTTP_REFERER']);
    if ($is_known_crawler && (strpos($uri, 'robots') !== false || strpos($uri, 'sitemap') !== false || strpos($uri, 'wp-admin') !== false)) {
        $d['crawler_violations'] = ($d['crawler_violations'] ?? 0) + 1;
        if ($d['crawler_violations'] > 3) {
            og_mark_block($d, 'atk', $now + 86400, 'crawler_suspicious_behavior', 'crawler_behavior', $rtLog, $ip, $uri);
            og_save($d, $ipf);
            $block('crawler_suspicious_behavior');
        }
    }
}

og_save($d, $ipf);

// ══════════════════════════════════════════════════════════════════
//  БЛОК 10 — АТАКИ (SQLi / XSS / LFI / RFI / CMD / SSTI / XXE)
// ══════════════════════════════════════════════════════════════════
$all_input = urldecode($uri);
if ($method === 'POST' && !empty($_POST)) $all_input .= ' ' . urldecode(http_build_query($_POST));
if (!empty($_COOKIE))                     $all_input .= ' ' . urldecode(http_build_query($_COOKIE));
if (!empty($_SERVER['HTTP_REFERER']))      $all_input .= ' ' . $_SERVER['HTTP_REFERER'];
$ai = strtolower($all_input);

$patterns = [
    'sql_injection' => [
        "' or '","' or 1","union select","union all select","drop table","drop database",
        "insert into","delete from","exec(","xp_cmdshell","sp_executesql","information_schema",
        "sleep(","benchmark(","waitfor delay","load_file(","into outfile","group_concat","/*!",
        "1=1--","1=1#","' --","';--","update set","cast(","convert(","substr(","substring(",
        "mid(","ascii(","char(","ord(","hex(","unhex(","base64","database()","user()","version(",
        "select * from","select count","select version","select user","select database",
        "; or 1--",") or (","or true","' and 1","and 1=1","where 1=1","having 1=1",
        "'; drop","' union","' and (","bulk insert","restore database",
    ],
    'xss' => [
        '<script','javascript:','onerror=','onload=','onclick=','onmouseover=',
        'onfocus=','onblur=','alert(','document.cookie','eval(','fromcharcode',
        'expression(','vbscript:','data:text/html','&#x','<iframe','<object',
        '<embed','src=javascript','href=javascript','<svg onload','<img ','<style',
        'onmousemove=','onmouseenter=','onmouseleave=','onwheel=','ontouchstart=',
        'onkeydown=','onkeyup=','onchange=','onsubmit=','ondblclick=','oncontextmenu=',
        'innerhtml','settimeout(','setinterval(','constructor(','prototype.',
        '<marquee','<bgsound','<isindex','<form','<input ','<button ','<textarea',
    ],
    'lfi' => [
        '../','..\\','%2e%2e%2f','..%2f','%2e%2e%5c','/etc/passwd','/etc/shadow',
        '/proc/self','c:\\windows','c:/windows','/boot.ini',
        'php://input','php://filter','expect://','phar://','zip://','data://',
        '/var/www','/home/','%00','null byte','.htpasswd','.ssh','authorized_keys',
        'config.php','wp-config','settings.php','database.yml','secrets.yml',
        '/proc/version','/etc/hosts','/etc/hostname','/etc/fstab','/root/.bash',
        'file://','glob://','var://','ogg://','rar://','sftp://',
    ],
    'cmd_injection' => [
        ';ls ',';id ','; cat ','; wget ','; curl ','; rm -','; uname',
        '|ls ','|id ','| cat ','| wget ','| curl ',
        '`ls`','`id`','`cat ','$(ls)','$(id)','$(cat',
        '&& ','|| ','> /tmp','< /etc','; nc ','; bash ','; sh ',
        'exec ','system ','passthru ','shell_exec ','proc_open ','backticks ',
        '&whoami','&ipconfig','&dir ','&net ','&systeminfo',
    ],
    'rfi' => [
        '=http%3a','=https%3a','=ftp%3a','?=http://','?=https://','?=ftp://',
        'include(','require(','include_once(','require_once(',
    ],
    'ssti' => [
        '{{','}}','${7*7}','#{7*7}','<%= 7*7 %>','jinja2','freemarker',
        'velocity','smarty','twig','${jndi:','#{','*{','[#','.?','[(#',
    ],
    'xxe' => [
        '<!entity','<!DOCTYPE','SYSTEM "file','SYSTEM \'file','<!doctype',
        '&xxe;','&lol;','&external','<!ELEMENT','PUBLIC ','SYSTEM ',
    ],
];
foreach ($patterns as $atk => $list) {
    foreach ($list as $p) {
        if (og_contains($ai, $p)) {
            og_mark_block($d, 'atk', $now + (int)$C['atk_block'], $atk, 'attack_pattern', $rtLog, $ip, $uri);
            og_save($d, $ipf);
            $block($atk, 400);
        }
    }
}

// ══════════════════════════════════════════════════════════════════
//  БЛОК 11 — АНОМАЛИИ ЗАПРОСА
// ══════════════════════════════════════════════════════════════════
if (strlen($uri) > 2000)                                    $block('uri_too_long', 400);
if (count($_GET) > 30 || count($_POST) > 50)                $block('too_many_params', 400);
if (!in_array($method, ['GET','POST','HEAD','OPTIONS']))     $block('bad_method', 405);

$scan_paths = [
    '/wp-admin','/wp-login','/phpmyadmin','/adminer','/admin.php',
    '/config.php','/.env','/.git/','/backup','/.htpasswd',
    '/shell.php','/c99.php','/r57.php','/xmlrpc.php','/wp-config',
    '/server-status','/server-info','/.DS_Store','/Thumbs.db',
    '/actuator','/api/swagger','/swagger.json','/openapi.json',
    '/aws.yml','/docker-compose','/.env.local','/.env.prod',
    '/Dockerfile','/composer.json','/package.json','/.npmrc',
    '/web.config','/applicationhost.config','/appsettings.json',
    '/autodiscover','/owa/','/exchange/','/ecp/',
    '/telescope','/horizon','/debugbar','/_ignition',
];
foreach ($scan_paths as $p) {
    if (og_contains(strtolower($uri), $p)) {
        og_mark_block($d, 'atk', $now + (int)$C['atk_block'], 'scan_path:' . $p, 'scan_path', $rtLog, $ip, $uri);
        og_save($d, $ipf);
        $block('scan_path', 404);
    }
}

// ══════════════════════════════════════════════════════════════════
//  БЛОК 12 — HONEYPOT
// ══════════════════════════════════════════════════════════════════
if ($method === 'POST' && !empty($_POST[$C['hp']])) {
    og_mark_block($d, 'atk', $now + (int)$C['atk_block'], 'honeypot', 'honeypot', $rtLog, $ip, $uri);
    og_save($d, $ipf);
    $block('honeypot');
}

// ══════════════════════════════════════════════════════════════════
//  БЛОК 13 — BEHAVIOR SCORE (токен из JS)
// ══════════════════════════════════════════════════════════════════
if ($method === 'POST' && isset($_POST[$C['bs_field']]) && $_POST[$C['bs_field']] !== '0') {
    $bs = (float)$_POST[$C['bs_field']];
    if ($bs < $C['bs_min']) $block('low_behavior_score', 403);
}

// ══════════════════════════════════════════════════════════════════
//  БЛОК 14 — DEVICE FINGERPRINT
// ══════════════════════════════════════════════════════════════════
if ($method === 'POST' && !empty($_POST[$C['fp_field']])) {
    $fp_token = preg_replace('/[^a-f0-9]/', '', $_POST[$C['fp_field']]);
    $fp_file  = $C['dir'] . '/fp_' . $fp_token . '.json';
    if (is_file($fp_file)) {
        $fp_data = json_decode(file_get_contents($fp_file), true) ?? [];
        $fp_ips  = $fp_data['ips'] ?? [];
        if (!in_array($ip, $fp_ips)) $fp_ips[] = $ip;
        $fp_data['ips']   = $fp_ips;
        $fp_data['count'] = ($fp_data['count'] ?? 0) + 1;
        if ($fp_data['count'] > 20 || count($fp_ips) > 5) {
            og_mark_block($d, 'atk', $now + (int)$C['atk_block'], 'suspicious_fingerprint', 'device_fingerprint', $rtLog, $ip, $uri);
            og_save($d, $ipf);
            $block('suspicious_fingerprint', 403);
        }
        @file_put_contents($fp_file, json_encode($fp_data), LOCK_EX);
    } else {
        @file_put_contents($fp_file, json_encode(['fp' => $fp_token, 'ips' => [$ip], 'count' => 1, 'first' => $now]), LOCK_EX);
    }
}

// ══════════════════════════════════════════════════════════════════
//  БЛОК 15 — ЗАЩИТА ОТ ЗЕРКАЛИРОВАНИЯ
// ══════════════════════════════════════════════════════════════════
$download_ua = [
    'httrack','teleport','offline explorer','website ripper','webcopier',
    'wget','curl','websuck','webzip','webwhacker','webacquirer','winhttrack',
    'sitesucker','devhd','grab','localcopy','sitescraper','sitegraber',
    'webstripper','webthumb','webslurp','webgrabber','webdump','webmirror',
    'webreaper','websnapshot','webdownloader','web downloader','grabber',
    'pavuk','archiver','wwwoffle','lynx','w3m','links ','elinks',
    'getright','flashget','download master','orbit downloader','mass downloader',
    'internet download manager','idm/','ftp downloader','download accelerator',
    'leechget','download express','downloadvip','streamdown','d9downloader',
    'htdig','discobot','offline','darcy ripper','deepnet explorer',
];
foreach ($download_ua as $dp) {
    if (og_contains($ua_l, $dp)) {
        og_mark_block($d, 'atk', $now + (int)$C['atk_block'], 'download_tool:' . $dp, 'download_tool', $rtLog, $ip, $uri);
        og_save($d, $ipf);
        $block('download_tool:' . $dp);
    }
}

$uri_clean     = strtolower(preg_replace('/\?.*$/', '', $uri));
$is_static     = (bool)preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|mp4|webm|mov|avi|mkv|m3u8|ts|webp|pdf|zip|rar)(\?|$)/i', $uri);
$url_hash      = md5($uri_clean);
$has_referer   = !empty($_SERVER['HTTP_REFERER']);
$human_score   = (float)($d['human_score'] ?? 0);
$human_ok      = $human_score >= 0.75 && !empty($d['human_last']) && ($now - (int)$d['human_last'] <= 600);
$is_html_nav   = !$is_static && $method === 'GET' && !in_array($req_path, ['/_site/v', '/_og_ping'], true);

// ── Скользящее окно уникальных URL (5 мин) — быстрый парсинг ────
$d['dl_urls']  = $d['dl_urls']  ?? [];
$d['dl_count'] = $d['dl_count'] ?? 0;
$d['dl_win']   = $d['dl_win']   ?? $now;

if ($now - $d['dl_win'] > 300) { $d['dl_urls'] = []; $d['dl_count'] = 0; $d['dl_win'] = $now; }
if (!in_array($url_hash, $d['dl_urls'], true)) {
    $d['dl_urls'][] = $url_hash;
    if (count($d['dl_urls']) > 500) $d['dl_urls'] = array_slice($d['dl_urls'], -500);
}
$d['dl_count']++;
$unique_url_cnt = count($d['dl_urls']);

$mirror_ban = static function (string $reason) use (&$d, $now, $C, $block, $ip, $ipf, $rtLog, $uri, $og_browser_shell_candidate, $og_is_origin): void {
    if ($og_is_origin && $og_browser_shell_candidate) {
        $d['suspect'] = min(100, (int)($d['suspect'] ?? 0) + 12);
        $d['suspect_reasons'][] = 'mirror_soft:' . $reason;
        $d['suspect_last'] = $now;
        og_save($d, $ipf);

        return;
    }
    $d['mirror_strikes'] = ($d['mirror_strikes'] ?? 0) + 1;
    $d['strikes_total']  = ($d['strikes_total']  ?? 0) + 1;
    $d['last_violation'] = $now;
    $banFor = (!$og_browser_shell_candidate && $d['mirror_strikes'] >= 2) ? $C['atk_block'] : ($C['rl_block'] * 2);
    og_mark_block($d, 'rl', $now + $banFor, $reason, 'mirror_guard', $rtLog, $ip, $uri);
    if (!$og_browser_shell_candidate && $d['mirror_strikes'] >= 2) {
        og_mark_block($d, 'atk', $now + $C['atk_block'], $reason, 'mirror_guard', $rtLog, $ip, $uri);
    }
    if (!$og_browser_shell_candidate && ($d['strikes_total'] ?? 0) >= (int)$C['perm_ban_after']) {
        og_perm_ban_add($C['perm_ban_file'], $ip, $reason, $rtLog, $uri, 'mirror_guard');
        og_mark_block($d, 'atk', $now + $C['atk_block'], $reason, 'mirror_guard', $rtLog, $ip, $uri);
        $d['perm_banned'] = 1;
    }
    og_save($d, $ipf);
    $block($reason, 429);
};

if ($unique_url_cnt >= 40) $mirror_ban('mirror_unique_urls');

// ══════════════════════════════════════════════════════════════════
// МЕДЛЕННЫЙ И СЕССИОННЫЙ ПАРСИНГ — единая логика
// ══════════════════════════════════════════════════════════════════

// ── 0. Глобальная карта сайта: учитываем все уникальные HTML-страницы ──
// Каждый запрос любого IP обновляет общую карту.
// Это позволяет знать реальный размер сайта и адаптировать пороги.
$siteMapFile = $C['dir'] . '/og_sitemap.json';
$siteMap     = [];
if (is_file($siteMapFile)) {
    $siteMap = json_decode(@file_get_contents($siteMapFile), true) ?? [];
}

if ($is_html_nav && !$is_static) {
    // Записываем страницу в карту сайта (hash → первый раз увиден)
    if (!isset($siteMap['pages'][$url_hash])) {
        $siteMap['pages'][$url_hash] = $now;
        // Ограничение: не более 5000 страниц
        if (count($siteMap['pages'] ?? []) > 5000) {
            asort($siteMap['pages']);
            $siteMap['pages'] = array_slice($siteMap['pages'], -5000, null, true);
        }
        $siteMap['updated'] = $now;
        @file_put_contents($siteMapFile, json_encode($siteMap), LOCK_EX);
    }
}

// Известный размер сайта (кол-во уникальных HTML-страниц)
$knownSiteSize = count($siteMap['pages'] ?? []);

// ── 1. Сессионный счётчик (TTL 30 мин) ──────────────────────────
$sessTTL = 1800;
if (($d['sess_start'] ?? 0) < $now - $sessTTL) {
    // Сессия завершилась — сохраняем снапшот для sessions.log
    if (!empty($d['_sess_snap']) && ($d['sess_html_cnt'] ?? 0) >= 1) {
        $snap = $d['_sess_snap'];
        $snap['sess_end'] = $now;
        $d['_prev_sess_data'] = $snap;
    }
    $d['sess_start']      = $now;
    $d['sess_urls']       = [];
    $d['sess_html_ts']    = [];
    $d['sess_noref']      = 0;
    $d['sess_html_cnt']   = 0;
    $d['sess_static_cnt'] = 0;
    unset($d['_sess_snap']);
}
$d['sess_last'] = $now;

if ($is_html_nav) {
    if (!in_array($url_hash, $d['sess_urls'] ?? [], true)) {
        $d['sess_urls'][] = $url_hash;
    }
    $d['sess_html_ts'][]  = $now;
    $d['sess_html_cnt']   = ($d['sess_html_cnt'] ?? 0) + 1;
    $d['sess_noref']      = ($d['sess_noref'] ?? 0) + (!$has_referer ? 1 : 0);
}
if ($is_static) {
    $d['sess_static_cnt'] = ($d['sess_static_cnt'] ?? 0) + 1;
}

$sessUniquePages = count($d['sess_urls'] ?? []);
$sessDuration    = max(1, $now - (int)$d['sess_start']); // сек с начала сессии
$sessHtml        = $d['sess_html_cnt'] ?? 0;
$sessStatic      = $d['sess_static_cnt'] ?? 0;

// ── 2. Обучение: собираем скорость навигации живых пользователей ─
// human_ok = true → этот IP подтверждён как человек.
// Записываем его скорость (стр/мин) в общую статистику.
// Потом используем медиану живых как эталон нормального поведения.
$navStatsFile = $C['dir'] . '/og_nav_stats.json';
$navStats     = is_file($navStatsFile)
    ? (json_decode(@file_get_contents($navStatsFile), true) ?? [])
    : [];

if ($human_ok && $sessUniquePages >= 2 && $sessDuration >= 10) {
    // Скорость в страницах/минуту этого пользователя
    $userSpeed = round($sessUniquePages / ($sessDuration / 60), 3);
    $navStats['speeds']   = $navStats['speeds'] ?? [];
    $navStats['speeds'][] = $userSpeed;
    // Храним последние 200 наблюдений
    if (count($navStats['speeds']) > 200) {
        $navStats['speeds'] = array_slice($navStats['speeds'], -200);
    }
    // Пересчитываем медиану и 90-й перцентиль
    $sorted = $navStats['speeds'];
    sort($sorted);
    $n = count($sorted);
    $navStats['median'] = $sorted[(int)floor($n * 0.5)];
    $navStats['p90']    = $sorted[(int)floor($n * 0.9)];
    $navStats['updated'] = $now;
    @file_put_contents($navStatsFile, json_encode($navStats), LOCK_EX);
}

// Эталонные скорости (стр/мин):
// Если накоплено >= 10 наблюдений — используем реальные данные сайта.
// Иначе — консервативные дефолты (2 стр/мин warn, 5 стр/мин block).
$enoughStats   = count($navStats['speeds'] ?? []) >= 10;
// Порог предупреждения = медиана живых × 2 (в 2 раза быстрее среднего человека)
// Порог блока = p90 живых × 3 (в 3 раза быстрее самого быстрого 10% людей)
$speedWarn  = $enoughStats ? (float)$navStats['median'] * 2.0 : 2.0;
$speedBlock = $enoughStats ? (float)$navStats['p90']    * 3.0 : 5.0;

// ── 3. Адаптивный % охвата сайта ────────────────────────────────
$siteMapMin   = (int)$C['site_map_min'];
$pctWarn      = (int)$C['scrape_pct_warn'];
$pctBlock     = (int)$C['scrape_pct_block'];

if ($knownSiteSize >= $siteMapMin) {
    $warnThreshold  = max(3, (int)ceil($knownSiteSize * $pctWarn  / 100));
    $blockThreshold = max(5, (int)ceil($knownSiteSize * $pctBlock / 100));
} else {
    $warnThreshold  = 4;
    $blockThreshold = 6;
}

// ── 4. Скоростная проверка: стр/мин за текущую сессию ───────────
// Считаем только если сессия идёт хотя бы 60 сек (чтобы не ложно
// срабатывать на первых секундах когда браузер грузит ресурсы).
if ($sessUniquePages >= 2 && $sessDuration >= 60 && !$human_ok && !$is_static) {
    $currentSpeed = $sessUniquePages / ($sessDuration / 60); // стр/мин

    if ($currentSpeed >= $speedBlock) {
        $d['suspect'] = min(100, ($d['suspect'] ?? 0) + 30);
        $d['scrape_speed_strikes'] = ($d['scrape_speed_strikes'] ?? 0) + 1;
        if (($d['suspect'] ?? 0) >= (int)$C['suspect_block'] || $d['scrape_speed_strikes'] >= 2) {
            $mirror_ban('scrape_speed_block:'.round($currentSpeed,2).'ppm');
        }
    } elseif ($currentSpeed >= $speedWarn) {
        $d['suspect'] = min(100, ($d['suspect'] ?? 0) + 12);
    }
}

// ── 5. % охвата за сессию (адаптивный) ──────────────────────────
if (!$human_ok && !$is_static) {
    if ($sessUniquePages >= $blockThreshold) {
        $d['suspect'] = min(100, ($d['suspect'] ?? 0) + 20);
        if (($d['suspect'] ?? 0) >= (int)$C['suspect_block']) {
            $mirror_ban('session_coverage:'.$sessUniquePages.'/'.$knownSiteSize);
        }
    } elseif ($sessUniquePages >= $warnThreshold) {
        $d['suspect'] = min(100, ($d['suspect'] ?? 0) + 8);
    }
}

// ── 6. HTML:static соотношение в сессии ─────────────────────────
if ($sessHtml >= 3) {
    if ($sessStatic === 0) {
        $d['suspect'] = min(100, ($d['suspect'] ?? 0) + 30);
        if (($d['suspect'] ?? 0) >= (int)$C['suspect_block']) {
            $mirror_ban('no_static_in_session');
        }
    } elseif ($sessHtml >= 5 && $sessStatic < $sessHtml) {
        $d['suspect'] = min(100, ($d['suspect'] ?? 0) + 10);
    }
}

// ── 7. Равномерные интервалы между страницами (настроенная задержка парсера) ──
$sessTsArr = $d['sess_html_ts'] ?? [];
if (count($sessTsArr) >= 8) {
    $ivs = [];
    for ($qi = 1; $qi < count($sessTsArr); $qi++) {
        $diff = $sessTsArr[$qi] - $sessTsArr[$qi - 1];
        if ($diff > 0 && $diff < 3600) $ivs[] = (float)$diff;
    }
    if (count($ivs) >= 6) {
        $mean = array_sum($ivs) / count($ivs);
        $sq   = array_reduce($ivs, static function ($c, $v) use ($mean) { return $c + ($v - $mean) ** 2; }, 0.0);
        $std  = sqrt($sq / count($ivs));
        // stddev < 3 сек при среднем > 8 сек = настроенная задержка
        if ($std < 3.0 && $mean > 8.0) {
            $d['suspect'] = min(100, ($d['suspect'] ?? 0) + 25);
            $d['slow_scrape_strikes'] = ($d['slow_scrape_strikes'] ?? 0) + 1;
            if ($d['slow_scrape_strikes'] >= 2 || ($d['suspect'] ?? 0) >= (int)$C['suspect_block']) {
                $mirror_ban('slow_scrape_timing:std='.round($std,2).',mean='.round($mean,1));
            }
        }
    }
}

// ── 8. Паттерн навигации: 70%+ без Referer = плагин ─────────────
if ($is_html_nav) {
    $d['nav_noref'] = ($d['nav_noref'] ?? 0) + (!$has_referer ? 1 : 0);
    $d['nav_total'] = ($d['nav_total'] ?? 0) + 1;
    if ($d['nav_total'] >= 5 && ($d['nav_noref'] / $d['nav_total']) >= 0.70) {
        $d['suspect'] = min(100, ($d['suspect'] ?? 0) + 20);
        if (($d['suspect'] ?? 0) >= (int)$C['suspect_block']) {
            $mirror_ban('plugin_direct_nav_pattern');
        }
    }
}

$d['no_ref_streak'] = $d['no_ref_streak'] ?? 0;
if (!$has_referer && $method === 'GET') $d['no_ref_streak']++;
else $d['no_ref_streak'] = max(0, ($d['no_ref_streak'] ?? 0) - 1);
// Блокируем только если много запросов подряд без Referer И уже много уникальных URL
if ($d['no_ref_streak'] > 8 && $unique_url_cnt > 15) $mirror_ban('mirror_no_referer_pattern');

$d['dl_ext_set'] = $d['dl_ext_set'] ?? [];
$req_ext = strtolower(pathinfo($uri_clean, PATHINFO_EXTENSION) ?: 'html');
if (!in_array($req_ext, $d['dl_ext_set'])) $d['dl_ext_set'][] = $req_ext;
// Блокируем только при реальном скачивании — много типов файлов И много URL
if (count($d['dl_ext_set']) >= 6 && $unique_url_cnt >= 20) $mirror_ban('mirror_all_filetypes');

// ── Human beacon ──────────────────────────────────────────────────
$human_score = (float)($d['human_score'] ?? 0);
if ($method === 'POST' && isset($_POST['_hs'])) {
    $hs = (float)$_POST['_hs'];
    $d['human_score'] = max($human_score, $hs);
    $human_score = $d['human_score'];
    og_save($d, $ipf);
}
if ((in_array($req_path, ['/_site/v', '/_og_ping'], true) || $isDirectBeacon) && $method === 'POST') {
    $ping_flag = $_POST['f'] ?? ''; // no_mouse|fake_mouse|cdp_marker|automation_script|webdriver_dom|cdc_window|…

    $flag_result = $og_apply_js_flag((string)$ping_flag, $d);
    if ($flag_result !== null) {
        og_save($d, $ipf);
        if ($C['log_on']) {
            og_log_extended($C['log'], $ip, $ua, $uri, ($flag_result['hard'] ? 'js_ban:' : 'js_flag:') . $ping_flag, 403, 'POST', [
                'suspect' => $d['suspect'],
            ]);
        }
        http_response_code(204); exit;
    }

    // Client Hints из JS (navigator.userAgentData) vs заголовки — подделанный UA / расширения
    $uad_raw = isset($_POST['uad']) ? (string)$_POST['uad'] : '';
    if ($uad_raw !== '' && strlen($uad_raw) < 4000) {
        $j = json_decode($uad_raw, true);
        if (is_array($j)) {
            $jsMob = !empty($j['m']);
            $hdr   = strtolower($_SERVER['HTTP_SEC_CH_UA_MOBILE'] ?? '');
            $chUa  = strtolower($_SERVER['HTTP_SEC_CH_UA'] ?? '');
            $mismatch = false;
            if ($hdr === '?1' && !$jsMob) {
                $mismatch = true;
            }
            if ($hdr === '?0' && $jsMob && $chUa !== '') {
                $mismatch = true;
            }
            $brands = $j['b'] ?? [];
            if (is_array($brands) && $chUa !== '') {
                $flat = '';
                foreach ($brands as $row) {
                    if (is_array($row) && isset($row[0])) {
                        $flat .= strtolower((string)$row[0]);
                    }
                }
                if ($flat !== '' && og_contains($ua_l, 'edg/') && !og_contains($flat, 'microsoft edge') && !og_contains($flat, 'edge')) {
                    $mismatch = true;
                }
                if ($flat !== '' && og_contains($ua_l, 'opr/') && !og_contains($flat, 'opera')) {
                    $mismatch = true;
                }
            }
            if ($mismatch) {
                $d['suspect'] = min(100, (int)($d['suspect'] ?? 0) + 45);
                $d['suspect_reasons'][] = 'uad_header_mismatch';
                $d['suspect_last'] = $now;
                og_save($d, $ipf);
                if ($C['log_on']) {
                    og_log_extended($C['log'], $ip, $ua, $uri, 'js_flag:uad_mismatch', 204, 'POST', ['suspect' => $d['suspect']]);
                }
                http_response_code(204);
                exit;
            }
            $d['uad_last'] = $now;
        }
    }

    // Обычный beacon — подтверждение человека
    $d['human_score'] = max($human_score, (float)($_POST['s'] ?? 0));
    $d['human_last']  = $now;
    og_save($d, $ipf);
    http_response_code(204); exit;
}

$human_score = (float)($d['human_score'] ?? $human_score);
$human_ok    = $human_score >= 0.75 && !empty($d['human_last']) && ($now - (int)$d['human_last'] <= 600);
$is_html_nav = !$is_static && $method === 'GET' && !in_array($req_path, ['/_site/v', '/_og_ping'], true);
$d['human_miss'] = $d['human_miss'] ?? 0;
if ($is_html_nav && !$human_ok) $d['human_miss']++;
elseif ($human_ok) $d['human_miss'] = 0;

$hmiss = (int)($C['human_miss_max'] ?? 3);
$hurls = (int)($C['human_miss_min_urls'] ?? 5);
if ($is_html_nav && !$human_ok && $d['human_miss'] > $hmiss && $unique_url_cnt > $hurls) {
    $d['suspect'] = min(100, (int)($d['suspect'] ?? 0) + 20);
    $d['suspect_reasons'][] = 'human_miss';
    og_save($d, $ipf);
    if ((int)($d['suspect'] ?? 0) >= (int)$C['suspect_block']) {
        $block('human_proof_required', 403);
    }
}
if ($is_static && !$has_referer && $unique_url_cnt > 80 && !$human_ok) {
    og_mark_block($d, 'atk', $now + (int)$C['atk_block'], 'static_mirror_without_human', 'static_mirror_guard', $rtLog, $ip, $uri);
    og_save($d, $ipf);
    $block('static_mirror_without_human', 429);
}

// ── Copy-only mirror probe (canonical Host → 404; mirror Host → ban) ──
foreach (['/.well-known/og-mirror-probe', '/_og_mirror_probe'] as $mp) {
    if (og_contains($uri_clean, $mp)) {
        $canonMp = og_canonical_host_cfg($C);
        $reqHostMp = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
        if ($canonMp !== '' && $reqHostMp !== '' && !og_host_matches_canonical($reqHostMp, $canonMp)) {
            og_perm_ban_add($C['perm_ban_file'], $ip, 'mirror_probe:' . $mp, $rtLog, $uri, 'mirror_probe');
            $d['perm_banned'] = 1;
            og_mark_block($d, 'atk', $now + (int)$C['atk_block'], 'mirror_probe:' . $mp, 'mirror_probe', $rtLog, $ip, $uri);
            og_save($d, $ipf);
            $block('mirror_probe', 403);
        }
        http_response_code(404);
        header('Cache-Control: no-store, no-cache');
        header('X-Robots-Tag: noindex');
        if ($method !== 'HEAD') {
            echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title> </title>'
                . '<style>html,body{margin:0;background:#fff}</style></head><body></body></html>';
        }
        exit;
    }
}

// ── Trap paths ────────────────────────────────────────────────────
$trapPaths = ['/_site/l','/_site/m','/_site/c','/._og_trap','/_og_trap','/.well-known/og-trap','/download-site','/mirror-site','/_og_bait','/_og_bait/'];
foreach ($trapPaths as $tp) {
    if (og_contains($uri_clean, $tp)) {
        $d['trap_hits'] = (int)($d['trap_hits'] ?? 0) + 1;
        $isBrowserish = (bool)preg_match('/mozilla|chrome|chromium|safari|firefox|edg|opr|crios|fxios/i', $ua);
        $isExplicitTool = (bool)preg_match('/curl|wget|python|scrapy|httpx|aiohttp|okhttp|bot|spider|crawler|headless|phantom|selenium|webdriver|playwright|puppeteer|java\/|go-http|requests|urllib|httpclient|libwww|axios/i', $ua);
        // Браузерные расширения/антивирусы иногда запрашивают "приманки" при загрузке страницы.
        // Для живых браузеров не эскалируем в perm_ban/atk_block с первого касания/перезагрузки.
        if ($isBrowserish && !$isExplicitTool && $d['trap_hits'] < 4) {
            og_mark_block($d, 'rl', $now + max(120, min(600, (int)$C['rl_block'])), 'trap_path_soft:' . $tp, 'trap_path', $rtLog, $ip, $uri);
            og_save($d, $ipf);
            $block('trap_path_soft', 429);
        }

        og_perm_ban_add($C['perm_ban_file'], $ip, 'trap_path:' . $tp, $rtLog, $uri, 'trap_path');
        $d['perm_banned'] = 1;
        og_mark_block($d, 'atk', $now + (int)$C['atk_block'], 'trap_path:' . $tp, 'trap_path', $rtLog, $ip, $uri);
        og_save($d, $ipf);
        $block('trap_path', 403);
    }
}

// ══════════════════════════════════════════════════════════════════
//  БЛОК P1 — НАВИГАЦИОННЫЙ ГРАФ
// ══════════════════════════════════════════════════════════════════
// Человек переходит по ссылкам нелинейно и выборочно.
// Парсер обходит ВСЕ ссылки подряд — граф становится полным (звезда/решётка).
// Храним: для каждой страницы — с каких страниц на неё пришли (Referer-граф).
// Если один IP имеет Referer → dest переходов > N при малом разбросе глубин — парсер.
if ($is_html_nav && $has_referer && !$is_static) {
    $ref_path  = strtolower(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH) ?? '');
    $ref_host  = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) ?? '';
    $own_host  = strtolower($_SERVER['HTTP_HOST'] ?? '');

    if ($ref_host === $own_host && $ref_path !== '' && $ref_path !== $uri_clean) {
        $d['nav_graph'] = $d['nav_graph'] ?? [];
        // Храним: src_hash → [dest_hash, ...]
        $src_h = substr(md5($ref_path), 0, 8);
        $dst_h = substr(md5($uri_clean), 0, 8);
        if (!isset($d['nav_graph'][$src_h])) $d['nav_graph'][$src_h] = [];
        if (!in_array($dst_h, $d['nav_graph'][$src_h], true)) {
            $d['nav_graph'][$src_h][] = $dst_h;
        }
        // Обрезаем граф до 200 узлов
        if (count($d['nav_graph']) > 200) {
            $d['nav_graph'] = array_slice($d['nav_graph'], -200, null, true);
        }

        // Считаем out-degree (сколько разных dest у каждого src)
        $total_edges   = 0;
        $high_deg_nodes = 0;
        foreach ($d['nav_graph'] as $srch => $dests) {
            $deg = count($dests);
            $total_edges += $deg;
            if ($deg >= 4) $high_deg_nodes++;
        }
        $node_count = count($d['nav_graph']);

        // Парсер: много узлов с высоким out-degree = систематический обход
        if ($node_count >= 5 && $high_deg_nodes >= 3) {
            $d['suspect'] = min(100, ($d['suspect'] ?? 0) + 25);
            $d['suspect_reasons'][] = 'nav_graph_full:nodes=' . $node_count . ',highdeg=' . $high_deg_nodes;
            if (($d['suspect'] ?? 0) >= (int)$C['suspect_block']) {
                $mirror_ban('nav_graph_crawler');
            }
        }
        // Экстремум: граф плотнее чем 2 выхода на узел в среднем
        if ($node_count >= 8 && $total_edges / $node_count > 2.5) {
            $d['suspect'] = min(100, ($d['suspect'] ?? 0) + 20);
            if (($d['suspect'] ?? 0) >= (int)$C['suspect_block']) {
                $mirror_ban('nav_graph_dense');
            }
        }
    }
}

// ══════════════════════════════════════════════════════════════════
//  БЛОК P2 — ДЕТЕКТ ФЕЙКОВОГО REFERER
// ══════════════════════════════════════════════════════════════════
// Парсер подставляет Referer = предыдущий запрошенный URL.
// Но реальный браузер: Referer совпадает с последней СТРАНИЦЕЙ где кликнули.
// Детектируем: Referer указывает на страницу которую этот IP ещё НЕ посещал.
if ($is_html_nav && $has_referer) {
    $ref_path_check = strtolower(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH) ?? '');
    $ref_host_check = strtolower(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) ?? '');
    $own_host_check = strtolower($_SERVER['HTTP_HOST'] ?? '');

    if ($ref_host_check === $own_host_check && $ref_path_check !== '') {
        $ref_hash_check = md5($ref_path_check);
        $visited = $d['sess_urls'] ?? [];
        // Если Referer ссылается на страницу которой нет в истории посещений — фейк
        if (!in_array($ref_hash_check, $visited, true) && count($visited) >= 2) {
            $d['fake_ref_count'] = ($d['fake_ref_count'] ?? 0) + 1;
            $d['suspect_reasons'][] = 'fake_referer:' . $ref_path_check;
            if ($d['fake_ref_count'] >= 3) {
                $d['suspect'] = min(100, ($d['suspect'] ?? 0) + 35);
                if (($d['suspect'] ?? 0) >= (int)$C['suspect_block']) {
                    $mirror_ban('fake_referer_pattern');
                }
            } elseif ($d['fake_ref_count'] >= 2) {
                $d['suspect'] = min(100, ($d['suspect'] ?? 0) + 15);
            }
        }
    }
}

// ══════════════════════════════════════════════════════════════════
//  БЛОК P3 — ДЕТЕКТ ПАРАМЕТРИЧЕСКОГО ПЕРЕБОРА
// ══════════════════════════════════════════════════════════════════
// Парсер систематически перебирает ?page=1,2,3... или ?id=1,2,3...
// Человек не делает 10+ запросов с одним и тем же параметром но разными значениями.
if (!empty($_SERVER['QUERY_STRING']) && $method === 'GET') {
    parse_str($_SERVER['QUERY_STRING'], $qp);
    foreach ($qp as $qk => $qv) {
        if (!is_numeric($qv) || strlen($qk) > 20) continue;
        $pkey = 'qparam_' . preg_replace('/[^a-z0-9_]/', '', strtolower($qk));
        $d[$pkey] = $d[$pkey] ?? [];
        $ival = (int)$qv;
        if (!in_array($ival, $d[$pkey], true)) {
            $d[$pkey][] = $ival;
            if (count($d[$pkey]) > 300) $d[$pkey] = array_slice($d[$pkey], -300);
        }
        $cnt = count($d[$pkey]);
        // Проверяем есть ли арифметическая прогрессия (парсер перебирает последовательно)
        if ($cnt >= 5) {
            sort($d[$pkey]);
            $is_seq = true;
            $diffs  = [];
            for ($pi = 1; $pi < min($cnt, 10); $pi++) {
                $diffs[] = $d[$pkey][$pi] - $d[$pkey][$pi - 1];
            }
            $uniq_diffs = array_unique($diffs);
            // Если все разницы одинаковы — это перебор (шаг 1, 2, 5 и т.д.)
            if (count($uniq_diffs) === 1) {
                $d['suspect'] = min(100, ($d['suspect'] ?? 0) + 20);
                $d['param_enum_strikes'] = ($d['param_enum_strikes'] ?? 0) + 1;
                $d['suspect_reasons'][] = 'param_enum:' . $qk . '=' . $qv;
                if ($d['param_enum_strikes'] >= 2 || $cnt >= 15) {
                    $mirror_ban('param_enumeration:' . $qk);
                }
            }
        }
        if ($cnt >= 20) {
            // Просто много разных значений одного числового параметра — подозрение
            $d['suspect'] = min(100, ($d['suspect'] ?? 0) + 15);
        }
    }
}

// ══════════════════════════════════════════════════════════════════
//  БЛОК P4 — URL-ШАБЛОННЫЙ ДЕТЕКТ
// ══════════════════════════════════════════════════════════════════
// Парсер запрашивает /category/1, /category/2, /item/abc, /item/xyz —
// один и тот же шаблон пути с разными значениями последнего сегмента.
if ($method === 'GET' && !$is_static) {
    $path_parts = explode('/', trim($uri_clean, '/'));
    if (count($path_parts) >= 2) {
        // Шаблон = всё кроме последнего сегмента
        $tpl = implode('/', array_slice($path_parts, 0, -1));
        $tpl_key = 'urltpl_' . substr(md5($tpl), 0, 12);
        $d[$tpl_key] = ($d[$tpl_key] ?? 0) + 1;
        if ($d[$tpl_key] >= 12) {
            $d['suspect'] = min(100, ($d['suspect'] ?? 0) + 20);
            $d['suspect_reasons'][] = 'url_template_enum:' . $tpl . ' x' . $d[$tpl_key];
            if ($d[$tpl_key] >= 20 || ($d['suspect'] ?? 0) >= (int)$C['suspect_block']) {
                $mirror_ban('url_template_crawler:' . $tpl);
            }
        }
    }
}

// ══════════════════════════════════════════════════════════════════
//  БЛОК P5 — MULTI-SESSION ДОЛГОСРОЧНЫЙ СЧЁТЧИК
// ══════════════════════════════════════════════════════════════════
// Парсер уходит и возвращается через часы/сутки, продолжая скачивание.
// Храним глобальный счётчик уникальных страниц за 7 дней.
$longKey  = 'long_urls';
$longWinK = 'long_win';
$d[$longWinK] = $d[$longWinK] ?? $now;
// Сброс раз в 7 дней
if (($now - (int)$d[$longWinK]) > 604800) {
    $d[$longKey]  = [];
    $d[$longWinK] = $now;
    $d['long_sess_count'] = 0;
}
$d[$longKey] = $d[$longKey] ?? [];
if (!in_array($url_hash, $d[$longKey], true)) {
    $d[$longKey][] = $url_hash;
    if (count($d[$longKey]) > 2000) $d[$longKey] = array_slice($d[$longKey], -2000);
}
$longUnique = count($d[$longKey]);
// Если за 7 дней один IP запросил >100 уникальных страниц — это парсер
if ($longUnique >= 100) {
    $d['suspect'] = min(100, ($d['suspect'] ?? 0) + 30);
    $d['suspect_reasons'][] = 'long_term_scrape:' . $longUnique . 'pages/7d';
    if ($longUnique >= 150 || ($d['suspect'] ?? 0) >= (int)$C['suspect_block']) {
        $mirror_ban('long_term_crawler:' . $longUnique . 'pages');
    }
} elseif ($longUnique >= 60) {
    $d['suspect'] = min(100, ($d['suspect'] ?? 0) + 15);
}
// Считаем кол-во отдельных сессий (каждый раз когда сессия сбрасывалась)
if (($d['sess_start'] ?? 0) < $now - $sessTTL) {
    $d['long_sess_count'] = ($d['long_sess_count'] ?? 0) + 1;
}
// Парсер возвращается много раз
if (($d['long_sess_count'] ?? 0) >= 5 && $longUnique >= 30) {
    $d['suspect'] = min(100, ($d['suspect'] ?? 0) + 20);
    if (($d['suspect'] ?? 0) >= (int)$C['suspect_block']) {
        $mirror_ban('multi_session_crawler');
    }
}

// ══════════════════════════════════════════════════════════════════
//  БЛОК P6 — АВТОКОРРЕЛЯЦИЯ ИНТЕРВАЛОВ (Ljung-Box упрощённый)
// ══════════════════════════════════════════════════════════════════
// Парсер с рандомными задержками всё равно имеет ненулевую автокорреляцию —
// задержки генерируются псевдослучайно и повторяются по паттерну.
// Человек имеет автокорреляцию ≈ 0 (истинно случайное поведение).
$sessTsArr2 = $d['sess_html_ts'] ?? [];
if (count($sessTsArr2) >= 12) {
    $ivs2 = [];
    for ($qi2 = 1; $qi2 < count($sessTsArr2); $qi2++) {
        $diff2 = $sessTsArr2[$qi2] - $sessTsArr2[$qi2 - 1];
        if ($diff2 > 0 && $diff2 < 1800) $ivs2[] = (float)$diff2;
    }
    if (count($ivs2) >= 10) {
        $n2   = count($ivs2);
        $mean2 = array_sum($ivs2) / $n2;
        // Вычисляем автокорреляцию lag=1
        $num = 0.0; $den = 0.0;
        for ($qi2 = 0; $qi2 < $n2; $qi2++) {
            $den += ($ivs2[$qi2] - $mean2) ** 2;
        }
        for ($qi2 = 1; $qi2 < $n2; $qi2++) {
            $num += ($ivs2[$qi2] - $mean2) * ($ivs2[$qi2 - 1] - $mean2);
        }
        $acf1 = ($den > 0) ? $num / $den : 0.0;
        $d['timing_acf1'] = round($acf1, 4);
        // Высокая положительная автокорреляция (>0.5) = псевдослучайный генератор
        if ($acf1 > 0.5) {
            $d['suspect'] = min(100, ($d['suspect'] ?? 0) + 30);
            $d['suspect_reasons'][] = 'timing_autocorr:acf1=' . round($acf1, 3);
            $d['acf_strikes'] = ($d['acf_strikes'] ?? 0) + 1;
            if ($d['acf_strikes'] >= 2 || ($d['suspect'] ?? 0) >= (int)$C['suspect_block']) {
                $mirror_ban('timing_autocorrelation:acf=' . round($acf1, 3));
            }
        }
        // Высокая отрицательная автокорреляция (<-0.5) = чередование коротких и длинных пауз (тоже бот)
        if ($acf1 < -0.5) {
            $d['suspect'] = min(100, ($d['suspect'] ?? 0) + 20);
            $d['suspect_reasons'][] = 'timing_neg_autocorr:acf1=' . round($acf1, 3);
        }
    }
}

// ══════════════════════════════════════════════════════════════════
//  БЛОК P7 — ДЕТЕКТ ОТСУТСТВИЯ ШРИФТОВ/ИКОНОК
// ══════════════════════════════════════════════════════════════════
// Реальный браузер ВСЕГДА загружает шрифты (woff2) и иконки (ico/png)
// указанные в CSS. Парсер/wget/curl качает только HTML и что найдёт в тегах,
// но не выполняет CSS для загрузки @font-face ресурсов.
if ($method === 'GET') {
    $is_font   = (bool)preg_match('/\.(woff2?|ttf|eot|otf)(\?|$)/i', $uri);
    $is_favicon = (bool)preg_match('/favicon|apple-touch-icon/i', $uri);
    if ($is_font || $is_favicon) {
        $d['font_loaded'] = ($d['font_loaded'] ?? 0) + 1;
    }
}
// Если уже много HTML-страниц но ни одного шрифта/иконки — подозрение
if (($sessHtml >= 4 || $sessUniquePages >= 4) && ($d['font_loaded'] ?? 0) === 0 && !$is_static) {
    $d['no_font_penalty'] = ($d['no_font_penalty'] ?? 0) + 1;
    if ($d['no_font_penalty'] >= 3) {
        $d['suspect'] = min(100, ($d['suspect'] ?? 0) + 20);
        $d['suspect_reasons'][] = 'no_font_requests:html=' . $sessHtml;
    }
    if ($d['no_font_penalty'] >= 6 || ($d['suspect'] ?? 0) >= (int)$C['suspect_block']) {
        $mirror_ban('no_font_scraper');
    }
}

// ══════════════════════════════════════════════════════════════════
//  БЛОК P8 — ДЕТЕКТ СИСТЕМАТИЧЕСКОЙ ГЛУБИНЫ URL
// ══════════════════════════════════════════════════════════════════
// Парсер методично проходит уровни: сначала все /cat/*, потом /cat/sub/*
// Человек прыгает по глубинам хаотично.
// Храним распределение глубин URL за сессию.
if ($method === 'GET' && !$is_static) {
    $depth = substr_count($uri_clean, '/');
    $d['depth_hist'] = $d['depth_hist'] ?? [];
    $d['depth_hist'][$depth] = ($d['depth_hist'][$depth] ?? 0) + 1;

    $total_depth_reqs = array_sum($d['depth_hist']);
    if ($total_depth_reqs >= 10) {
        // Смотрим: если 80%+ запросов на одной глубине — методичный парсер
        $max_depth_cnt = max($d['depth_hist']);
        $depth_ratio   = $max_depth_cnt / $total_depth_reqs;
        if ($depth_ratio >= 0.85 && $total_depth_reqs >= 15) {
            $d['suspect'] = min(100, ($d['suspect'] ?? 0) + 15);
            $d['suspect_reasons'][] = 'depth_uniform:ratio=' . round($depth_ratio, 2);
        }
        // Или: переход строго от малой глубины к большей (BFS-обход)
        $depths = array_keys($d['depth_hist']);
        sort($depths);
        if (count($depths) >= 3) {
            $depth_diffs = [];
            for ($di = 1; $di < count($depths); $di++) {
                $depth_diffs[] = $depths[$di] - $depths[$di - 1];
            }
            if (count(array_unique($depth_diffs)) === 1 && $depth_diffs[0] === 1) {
                // Строго BFS: 1→2→3→4... глубин
                $d['suspect'] = min(100, ($d['suspect'] ?? 0) + 20);
                $d['suspect_reasons'][] = 'bfs_depth_pattern';
                $d['bfs_strikes'] = ($d['bfs_strikes'] ?? 0) + 1;
                if ($d['bfs_strikes'] >= 2 || ($d['suspect'] ?? 0) >= (int)$C['suspect_block']) {
                    $mirror_ban('bfs_crawler_pattern');
                }
            }
        }
    }
}

// ══════════════════════════════════════════════════════════════════
//  БЛОК P9 — ЭНТРОПИЯ URL-ПУТЕЙ
// ══════════════════════════════════════════════════════════════════
// Парсер запрашивает очень разнообразные URL (высокая энтропия = скачивает всё).
// Человек ходит по небольшому кластеру похожих страниц.
if ($sessUniquePages >= 8 && !$is_static) {
    // Считаем энтропию Шеннона по первым сегментам пути
    $seg_counts = [];
    foreach ($d['sess_urls'] ?? [] as $uh) {
        // Восстановить первый сегмент из hash невозможно — используем сам hash как символ
        // Вместо этого используем dl_ext_set как прокси-метрику разнообразия
        $seg_counts[$uh] = ($seg_counts[$uh] ?? 0) + 1;
    }
    $total_e = array_sum($seg_counts);
    $entropy = 0.0;
    foreach ($seg_counts as $cnt_e) {
        $p = $cnt_e / $total_e;
        if ($p > 0) $entropy -= $p * log($p, 2);
    }
    // Максимальная энтропия = log2(N) при равномерном распределении
    // Парсер: каждая страница запрошена ровно 1 раз → энтропия = log2(N) (максимум)
    $max_entropy = log($sessUniquePages, 2);
    $entropy_ratio = ($max_entropy > 0) ? $entropy / $max_entropy : 0;
    $d['url_entropy'] = round($entropy_ratio, 3);

    if ($entropy_ratio >= 0.97 && $sessUniquePages >= 12) {
        // Почти идеальная равномерность → парсер (каждый URL ровно 1 раз)
        $d['suspect'] = min(100, ($d['suspect'] ?? 0) + 25);
        $d['suspect_reasons'][] = 'url_entropy_max:' . round($entropy_ratio, 3);
        $d['entropy_strikes'] = ($d['entropy_strikes'] ?? 0) + 1;
        if ($d['entropy_strikes'] >= 2 || ($d['suspect'] ?? 0) >= (int)$C['suspect_block']) {
            $mirror_ban('max_entropy_crawler');
        }
    }
}

og_save($d, $ipf);

// ══════════════════════════════════════════════════════════════════
//  ОЧИСТКА СТАРЫХ ФАЙЛОВ (1% запросов)
// ══════════════════════════════════════════════════════════════════
if (mt_rand(1, 100) === 1) {
    $cutoff = $now - 3600;
    foreach (glob($C['dir'] . '/*.json') ?: [] as $f) {
        $fd    = json_decode(@file_get_contents($f), true) ?? [];
        $alive = (!empty($fd['atk_block']) && $fd['atk_block'] > $now)
              || (!empty($fd['rl_block'])  && $fd['rl_block']  > $now);
        if (!$alive && filemtime($f) < $cutoff) @unlink($f);
    }
    foreach (glob($C['dir'] . '/geo_*.txt') ?: [] as $f) if (filemtime($f) < $now - 86400) @unlink($f);
    foreach (glob($C['dir'] . '/abuse_*.json') ?: [] as $f) if (filemtime($f) < $now - 3600) @unlink($f);
    foreach (glob($C['dir'] . '/fp_*.json') ?: [] as $f) if (filemtime($f) < $now - 604800) @unlink($f);
    foreach (glob($C['dir'] . '/sub_*.json') ?: [] as $f) if (filemtime($f) < $now - 3600) @unlink($f);
}

// ✓ Все проверки пройдены

// ══════════════════════════════════════════════════════════════════
//  TRAFFIC LOG — каждый разрешённый запрос
// ══════════════════════════════════════════════════════════════════
if ($C['traffic_log_on']) {

    // Классифицируем тип визитора
    $tl_class = 'human';
    if (($d['suspect'] ?? 0) >= 50)          $tl_class = 'suspect';
    if (($d['suspect'] ?? 0) >= (int)$C['suspect_block']) $tl_class = 'bot';
    if (!empty($d['proxy_rotation']))         $tl_class = 'proxy';
    if (($d['long_sess_count'] ?? 0) >= 3)   $tl_class = 'crawler';

    // Формируем сигналы подозрения
    $tl_signals = implode(',', array_unique(array_slice($d['suspect_reasons'] ?? [], -5)));

    // Профиль запроса
    $tl_accept_enc = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '-';
    $tl_accept_lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '-';
    $tl_sec_fetch  = isset($_SERVER['HTTP_SEC_FETCH_SITE']) ? 'yes' : 'no';
    $tl_cookie_cnt = count($_COOKIE);
    $tl_is_xhr     = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) ? 'xhr' : 'nav';

    // Поведенческие метрики сессии
    $tl_speed = '–';
    if (($d['sess_start'] ?? 0) > 0 && ($sessUniquePages ?? 0) >= 2) {
        $dur = max(1, $now - (int)$d['sess_start']);
        $tl_speed = round(($sessUniquePages / ($dur / 60)), 2) . 'p/m';
    }

    $tl_line = implode("\t", [
        date('Y-m-d H:i:s'),          // время
        $ip,                           // IP
        $tl_class,                     // классификация
        ($d['suspect'] ?? 0),          // suspect score
        $method,                       // метод
        substr($uri, 0, 120),          // URL (обрезан)
        $is_static ? 'static' : 'html',// тип ресурса
        $has_referer ? substr($_SERVER['HTTP_REFERER'], 0, 80) : '-', // Referer
        substr($ua, 0, 100),           // UA
        $tl_accept_lang,               // Accept-Language
        $tl_accept_enc,                // Accept-Encoding
        $tl_sec_fetch,                 // Sec-Fetch заголовки есть?
        $tl_cookie_cnt,                // кол-во кук
        $tl_is_xhr,                    // XHR или навигация
        $sessUniquePages ?? 0,         // уникальных страниц в сессии
        $tl_speed,                     // скорость стр/мин
        $longUnique ?? 0,              // всего уникальных за 7 дней
        $tl_signals ?: '–',            // сигналы подозрения
    ]);

    if (is_file($C['traffic_log']) && filesize($C['traffic_log']) > $C['traffic_log_max']) {
        @rename($C['traffic_log'], $C['traffic_log'] . '.' . date('YmdHis') . '.bak');
    }
    @file_put_contents($C['traffic_log'], $tl_line . "\n", FILE_APPEND | LOCK_EX);
}

// ══════════════════════════════════════════════════════════════════
//  SESSIONS LOG — итог сессии при смене (новая сессия = предыдущая завершилась)
// ══════════════════════════════════════════════════════════════════
if ($C['sessions_log_on'] && !empty($d['_prev_sess_data'])) {
    $ps = $d['_prev_sess_data'];
    $sl_class = 'human';
    if (($ps['suspect'] ?? 0) >= 50) $sl_class = 'suspect';
    if (($ps['suspect'] ?? 0) >= (int)$C['suspect_block']) $sl_class = 'bot';
    if (($ps['long_sess_count'] ?? 0) >= 3) $sl_class = 'crawler';

    $sl_dur = max(0, ($ps['sess_end'] ?? $now) - ($ps['sess_start'] ?? $now));
    $sl_ppm = ($sl_dur > 0 && ($ps['sess_unique'] ?? 0) >= 2)
        ? round(($ps['sess_unique'] / ($sl_dur / 60)), 2) : 0;

    $sl_line = implode("\t", [
        date('Y-m-d H:i:s', $ps['sess_end'] ?? $now),
        $ps['ip'] ?? $ip,
        $sl_class,
        $ps['suspect'] ?? 0,
        $ps['sess_unique'] ?? 0,       // уникальных страниц
        $ps['sess_html_cnt'] ?? 0,     // HTML запросов
        $ps['sess_static_cnt'] ?? 0,   // статика запросов
        $sl_dur . 's',                 // длительность сессии
        $sl_ppm . 'p/m',               // скорость
        $ps['font_loaded'] ?? 0,       // шрифтов загружено
        $ps['fake_ref_count'] ?? 0,    // фейковых Referer
        $ps['nav_total'] ?? 0,         // всего навигаций
        round(($ps['url_entropy'] ?? 0), 3), // энтропия URL
        round(($ps['timing_acf1'] ?? 0), 3), // автокорреляция тайминга
        round(($ps['timing_std'] ?? 0), 3),  // stddev тайминга
        $ps['long_sess_count'] ?? 0,   // кол-во сессий за 7 дней
        $ps['long_unique'] ?? 0,       // уникальных страниц за 7 дней
        substr($ps['ua'] ?? '-', 0, 100),
        implode(',', array_unique(array_slice($ps['suspect_reasons'] ?? [], 0, 10))),
    ]);

    if (is_file($C['sessions_log']) && filesize($C['sessions_log']) > $C['sessions_log_max']) {
        @rename($C['sessions_log'], $C['sessions_log'] . '.' . date('YmdHis') . '.bak');
    }
    @file_put_contents($C['sessions_log'], $sl_line . "\n", FILE_APPEND | LOCK_EX);
    unset($d['_prev_sess_data']);
}

// Сохраняем данные текущей сессии для записи в sessions.log при следующей смене
$d['_sess_snap'] = [
    'ip'              => $ip,
    'ua'              => $ua,
    'suspect'         => $d['suspect'] ?? 0,
    'suspect_reasons' => array_unique(array_slice($d['suspect_reasons'] ?? [], -20)),
    'sess_start'      => $d['sess_start'] ?? $now,
    'sess_unique'     => $sessUniquePages ?? 0,
    'sess_html_cnt'   => $d['sess_html_cnt'] ?? 0,
    'sess_static_cnt' => $d['sess_static_cnt'] ?? 0,
    'font_loaded'     => $d['font_loaded'] ?? 0,
    'fake_ref_count'  => $d['fake_ref_count'] ?? 0,
    'nav_total'       => $d['nav_total'] ?? 0,
    'url_entropy'     => $d['url_entropy'] ?? 0,
    'timing_acf1'     => $d['timing_acf1'] ?? 0,
    'timing_std'      => $d['timing_std'] ?? 0,
    'long_sess_count' => $d['long_sess_count'] ?? 0,
    'long_unique'     => $longUnique ?? 0,
];
})();
BP;




$JS_BLOCK = <<<'JS'
<!-- [OfferGuard:start] -->
<script>
(function(){"use strict";
if(window.__ogGuardLoaded)return;
window.__ogGuardLoaded=1;
/* OfferGuard v5 */
/* Без статического og-css в HTML: display:contents на [data-og] ломал вёрстку / мог «протекать» текстом.
   Лоадер и .og-canvas-text — только через инжект в head. Скрытие #og-content — inline в оболочке + _ogForceReveal. */
try{
var _ogS=document.createElement("style");
_ogS.textContent="#_og_loader{position:fixed;inset:0;z-index:99999;background:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;transition:opacity .4s}#_og_loader.og-hide{opacity:0;pointer-events:none}#_og_loader._gone{display:none}#_og_shield{width:72px;height:72px;margin-bottom:18px;animation:_og_pulse 1.6s ease-in-out infinite}@keyframes _og_pulse{0%,100%{transform:scale(1);opacity:1}50%{transform:scale(1.12);opacity:0.7}}#_og_loader p{margin:0;font:14px/1.5 sans-serif;color:#aaa;letter-spacing:.04em}.og-canvas-text{user-select:none;-webkit-user-select:none;pointer-events:none}";
(document.head||document.documentElement).appendChild(_ogS);
}catch(e){}

// ── Конфиг ────────────────────────────────────────────────────
var C={
    beacon:"/bot-protect.php?_og_ep=v",
    beaconFallbacks:["bot-protect.php?_og_ep=v","../bot-protect.php?_og_ep=v","../../bot-protect.php?_og_ep=v","../../../bot-protect.php?_og_ep=v"],
    liveGate:"/_site/s",
    liveGateFallbacks:["/bot-protect.php?_og_ep=s","bot-protect.php?_og_ep=s","../bot-protect.php?_og_ep=s","../../bot-protect.php?_og_ep=s","../../../bot-protect.php?_og_ep=s"],
    livePubStorageKey:"_ogLt5",
    liveTabStorageKey:"_ogT1",
    liveHookOneTab:true,
    liveFetchHook:true,
    liveRequired:false,
    expectedHost:"",
    copyGuardStrict:true,
    copyDefenseBase:true,
    copyDefenseCss:true,
    copyDefenseEmbed:true,
    copyDefenseUiLock:true,
    copyDefenseHotkeys:true,
    copyHotkeyOriginSoft:true,
    copyDefenseHostReverify:true,
    hostReverifyMs:4500,
    liveTimeout:6000,
    liveWebhookStrict:false,
    liveBeatInterval:8000,
    liveKeySplit:false,
    kfGraceMs:2500,
    kfBeatTtl:8,
    sseEnabled:true,
    serializerHardKill:false,
    livePubTtl:60,
    livePubRefreshMs:45000,
    softFailOpen:true,
    deferAssets:true,
    autoProtectFull:false,
    devtoolsKill:false,
    debugKill:false,
    maxScore:12,
    threshold:0.65,
    minTime:900,       // короткая проверка перед монтажом лендинга
    lsKey:"_og5",
    lsMax:25,
    dtInterval:1500,
    killDelay:120,     // мс задержки перед killPage (чтоб не мелькало у человека)
    pluginBadScore:4,  // сколько очков suspect = kill при детекте плагина
    parserKill:true,   // строгий режим: признаки парсинга сразу гасят DOM и банят IP
    botTimeout:12000   // мс без событий при фокусе; live-key на нормальном браузере открывает страницу раньше
};
function _ogNavIsReload(){
    try{
        var nav=performance.getEntriesByType&&performance.getEntriesByType("navigation");
        if(nav&&nav[0]&&nav[0].type==="reload")return true;
    }catch(e){}
    try{return performance.navigation&&performance.navigation.type===1;}catch(e2){}
    return false;
}

// ── Состояние ─────────────────────────────────────────────────
var score=0,gesture=false,dead=false,deniedShown=false,pageStart=Date.now(),liveOk=false,livePending=false,liveToken="",livePayloadKey="",liveSid="",livePub="",_ogAssetMasterKey="";
var _ogKbase="",_ogKfBoot="",_ogKfrag="",_ogKfBucket=0,_ogKfExp=0,_ogSplitOn=false,_ogKfWatch=null,_ogShadowRef=null;
// ── Client-side xlog ─────────────────────────────────────────
// Уровни: debug/info/warn/error/fatal. Шлёт POST /_site/x (см. серверный handler).
// Также дублирует в console.log с префиксом [OG:LVL] для DevTools.
// Дросселинг: не более 30 сообщений за сессию (anti-flood).
var _ogXlogQuota=30,_ogXlogSent=0,_ogXlogLvlRank={debug:10,info:20,warn:30,error:40,fatal:50};
function _ogXlog(lvl,fn,msg,ctx){
    try{
        lvl=String(lvl||"info").toLowerCase();
        var lr=_ogXlogLvlRank[lvl]||20;
        var min=_ogXlogLvlRank[String((C&&C.xlogLevel)||"debug").toLowerCase()]||10;
        if(lr<min)return;
        var pref="[OG:"+lvl.toUpperCase()+"] "+fn+": "+msg;
        try{
            if(lr>=40&&window.console&&console.error)console.error(pref,ctx||{});
            else if(lr>=30&&window.console&&console.warn)console.warn(pref,ctx||{});
            else if(window.console&&console.log)console.log(pref,ctx||{});
        }catch(_e){}
        if(_ogXlogSent>=_ogXlogQuota)return;
        _ogXlogSent++;
        var body=JSON.stringify({level:lvl,fn:String(fn||"").slice(0,60),msg:String(msg||"").slice(0,500),ctx:ctx||{}});
        var urls=["/_site/x","/bot-protect.php?_og_ep=x","bot-protect.php?_og_ep=x"];
        function _try(i){
            if(i>=urls.length)return;
            try{
                fetch(urls[i],{method:"POST",headers:{"Content-Type":"application/json"},body:body,keepalive:true,credentials:"same-origin"})
                    .catch(function(){_try(i+1);});
            }catch(_e2){_try(i+1);}
        }
        _try(0);
    }catch(_eOut){}
}
function _ogXlogErr(fn,err,ctx){
    var c=ctx||{};
    try{
        c.err_name=err&&err.name||"";
        c.err_msg=err&&err.message||String(err);
        c.err_stack=err&&err.stack?String(err.stack).slice(0,400):"";
    }catch(_e){}
    _ogXlog("error",fn,c.err_msg||"exception",c);
}
try{window.addEventListener("error",function(ev){
    _ogXlog("error","window.onerror",String(ev&&ev.message||"error"),{src:String(ev&&ev.filename||"").slice(-80),line:ev&&ev.lineno,col:ev&&ev.colno});
});}catch(_eg){}
try{window.addEventListener("unhandledrejection",function(ev){
    var r=ev&&ev.reason;_ogXlog("error","unhandledrejection",String(r&&r.message||r||"rejection"),{stack:r&&r.stack?String(r.stack).slice(0,300):""});
});}catch(_eh){}
_ogXlog("info","guard_boot","JS guard loaded",{ua:String(navigator.userAgent||"").slice(0,80),href:location.href.slice(0,80),split_cfg:!!(C&&C.liveKeySplit)});
function _ogMetaExpectedHost(){
    try{
        var m=document.querySelector('meta[name="og-origin-host"]');
        if(!m)return"";
        return String(m.getAttribute("content")||"").trim().toLowerCase();
    }catch(e){return"";}
}
function _ogEffectiveExpectedHost(){
    var h=String(C.expectedHost||"").trim().toLowerCase();
    if(h)return h;
    return _ogMetaExpectedHost();
}
function _ogScopedSk(base){
    var b=String(base||"").trim();
    if(!b)return"_ogLt5";
    var e=_ogEffectiveExpectedHost();
    if(!e)return b;
    return b+"@"+e.replace(/[^\w.-]+/g,"_");
}
function _ogLiveTabSk(){return _ogScopedSk(C.liveTabStorageKey||"_ogT1");}
function _ogTabId(){
    var sk=_ogLiveTabSk(),t="";
    try{t=sessionStorage.getItem(sk)||"";}catch(e){}
    if(/^[a-f0-9-]{16,64}$/i.test(t))return t;
    try{t=(crypto&&crypto.randomUUID)?crypto.randomUUID():(Math.random().toString(16).slice(2)+Date.now().toString(16));}catch(e2){t=Math.random().toString(16).slice(2)+Date.now().toString(16);}
    try{sessionStorage.setItem(sk,t);}catch(e3){}
    return t;
}
function _ogLiveHookBcName(chal){
    var e=_ogEffectiveExpectedHost()||String(location.host||"").toLowerCase();
    return "og-live-"+String(chal||"").slice(0,24)+"@"+e.replace(/[^\w.-]+/g,"_");
}
function _ogTabLimitClientReact(){
    if(_ogCopyGuardActive()){kill();return;}
    try{if(window.opener||history.length<2)window.close();}catch(e){}
    softFlag("tab_limit");
}
if(_ogNavIsReload()){
    try{sessionStorage.removeItem(_ogScopedSk(C.livePubStorageKey||"_ogLt5"));sessionStorage.removeItem(C.livePubStorageKey||"_ogLt5");}catch(e){}
}
function _ogHostMatchesExpected(actualHost,expectedHost){
    actualHost=String(actualHost||"").trim().toLowerCase();
    expectedHost=String(expectedHost||"").trim().toLowerCase();
    if(!expectedHost)return true;
    if(!actualHost)return false;
    if(actualHost===expectedHost)return true;
    function _ogStripWww(h){return h.indexOf("www.")===0?h.slice(4):h;}
    var a=_ogStripWww(actualHost.replace(/:\d+$/,""));
    var e=_ogStripWww(expectedHost.replace(/:\d+$/,""));
    return a===e;
}
function _ogGatePayloadHostOk(pH){
    var h=String(pH||"").trim().toLowerCase();
    if(!h)return false;
    var lh=String(location.host||"").trim().toLowerCase();
    var lhn=String(location.hostname||"").trim().toLowerCase();
    return _ogHostMatchesExpected(lh,h)||_ogHostMatchesExpected(lhn,h)||lh===h||lhn===h;
}
function _ogOriginSessionOk(eh){
    eh=eh||_ogEffectiveExpectedHost();
    try{
        var m=document.querySelector('meta[name="og-origin-session"]');
        var tok=m&&String(m.getAttribute("content")||"").trim();
        if(!tok||tok.length<12)return false;
        eh=String(eh||"").trim().toLowerCase();
        if(!eh)return false;
        var pr=String(location.protocol||"");
        if(pr!=="https:"&&pr!=="http:")return false;
        var lh=String(location.hostname||"").toLowerCase();
        return !!(lh&&_ogHostMatchesExpected(lh,eh));
    }catch(e){}
    return false;
}
function _ogIsCopyContext(){
    try{
        if(_ogOriginSessionOk())return false;
        if(String(location.protocol||"")==="file:")return true;
        var e=_ogEffectiveExpectedHost();
        if(!e)return false;
        try{
            var om=document.querySelector('meta[name="og-origin-host"]');
            if(om){
                var mh=String(om.getAttribute("content")||"").trim().toLowerCase();
                if(mh&&e&&!_ogHostMatchesExpected(mh,e))return true;
                var lhn0=String(location.hostname||"").toLowerCase();
                if(e&&lhn0&&!_ogHostMatchesExpected(lhn0,e))return true;
            }
        }catch(xm){}
        var lh=String(location.host||"").toLowerCase();
        var lhn=String(location.hostname||"").toLowerCase();
        if(_ogHostMatchesExpected(lh,e)||_ogHostMatchesExpected(lhn,e))return false;
        try{
            var oh=String((location.origin&&location.origin!=="null")?new URL(location.origin).hostname:"").toLowerCase();
            if(oh&&_ogHostMatchesExpected(oh,e))return false;
        }catch(x){}
        return true;
    }catch(x){return false;}
}
function _ogCopyGuardActive(){
    return C.copyGuardStrict!==false&&_ogIsCopyContext();
}
function _ogOriginSoftNeverKill(){
    return !_ogCopyGuardActive()&&(C.softFailOpen===true||C.autoProtectFull===true);
}
function _ogOgContentVisible(){
    try{
        var ogc=document.getElementById("og-content");
        if(!ogc)return false;
        var cs=window.getComputedStyle?getComputedStyle(ogc):null;
        return !!(cs&&cs.display!=="none"&&cs.visibility!=="hidden");
    }catch(e){return false;}
}
function _ogSoftOriginReveal(reason){
    if(!_ogOriginSoftNeverKill())return false;
    softFlag(reason||"origin_soft");
    try{beacon(score||C.maxScore);}catch(e){}
    try{
        var ogc=document.getElementById("og-content");
        if(ogc)_ogRevealFallback(ogc);
    }catch(e2){}
    return true;
}
function _ogStripOgContent(){
    try{
        var o=document.getElementById("og-content");
        if(o){o.innerHTML="";o.style.setProperty("display","none","important");o.style.setProperty("visibility","hidden","important");o.style.setProperty("opacity","0","important");
        try{o.querySelectorAll("*").forEach(function(n){n.style.setProperty("display","none","important");n.style.setProperty("visibility","hidden","important");});}catch(e2){}}
        var tpl=document.getElementById("og-origin-plain");
        if(tpl&&tpl.parentNode)tpl.parentNode.removeChild(tpl);
    }catch(e){}
}
function _ogStripPromotedHeadStyles(){
    try{
        document.querySelectorAll("head link[data-og-promoted='1']").forEach(function(l){
            if(l.parentNode)l.parentNode.removeChild(l);
        });
    }catch(e){}
}
function _ogClearClientStores(){
    var sk=C.livePubStorageKey||"_ogLt5";
    try{sessionStorage.removeItem(sk);}catch(e){}
    try{sessionStorage.removeItem(C.lsKey);}catch(e){}
    try{localStorage.removeItem(C.lsKey);}catch(e){}
    try{localStorage.removeItem(sk);}catch(e){}
}
// Tamper probe: CSS token / <base> hijack on copies (never vetoes kill on mirror — always signals tamper).
function _ogCopyDefenseTampered(){
    if(!_ogCopyGuardActive())return false;
    var e=_ogEffectiveExpectedHost();
    if(!e)return false;
    if(C.copyDefenseCss!==false){
        try{
            var cv=getComputedStyle(document.documentElement).getPropertyValue("--og-canonical-host");
            cv=String(cv||"").trim().replace(/^["'\s]+|["'\s]+$/g,"").toLowerCase();
            if(cv&&cv!==e&&!_ogHostMatchesExpected(cv,e))return true;
        }catch(x){}
    }
    if(C.copyDefenseBase!==false){
        try{
            var base=document.querySelector("base[href]");
            if(base){
                var bh=String(new URL(base.getAttribute("href"),location.href).hostname||"").toLowerCase();
                if(bh&&!_ogHostMatchesExpected(bh,e))return true;
            }
            var du=String(new URL(document.baseURI).hostname||"").toLowerCase();
            if(du&&!_ogHostMatchesExpected(du,e))return true;
        }catch(x){}
    }
    return false;
}
function _ogCopyDefenseExtras(){
    try{if(_ogCopyDefenseTampered())return true;}catch(x){}
    return true;
}
function _ogCopyFamilyBad(p){
    if(!p||typeof p!=="object")return false;
    if(p.copy_rejected||p.family==="denied"||p.accepted===false)return true;
    var r=String(p.reason||"");
    return /^(copy_rejected|copy_host_denied|copy_not_family)/.test(r)||(p.ok===true&&p.family&&p.family!=="accepted");
}
function _ogCopyHostReverify(){
    if(C.copyDefenseHostReverify===false)return;
    var ms=_ogCopyGuardActive()?2000:Math.max(2500,parseInt(C.hostReverifyMs,10)||4500);
    function tick(){
        if(dead)return;
        if(_ogIsCopyContext()||_ogCopyDefenseTampered())_ogCopyKillImmediate();
    }
    setInterval(tick,ms);
    document.addEventListener("visibilitychange",tick);
    window.addEventListener("pageshow",tick);
    window.addEventListener("focus",tick);
}
function _ogCopyEmbedWatch(){
    if(C.copyDefenseEmbed===false||!_ogEffectiveExpectedHost())return;
    var e=_ogEffectiveExpectedHost();
    function _ogEmbedKill(){
        if(_ogCopyGuardActive()&&!dead)_ogCopyKillImmediate();
    }
    try{
        if(window.top!==window.self){
            var th=String(window.top.location.hostname||"").toLowerCase();
            if(th&&!_ogHostMatchesExpected(th,e))_ogEmbedKill();
        }
    }catch(x){
        try{
            if(document.referrer){
                var rh=String(new URL(document.referrer).hostname||"").toLowerCase();
                if(rh&&!_ogHostMatchesExpected(rh,e)&&_ogCopyGuardActive())_ogEmbedKill();
            }
        }catch(x2){}
    }
    if(typeof MutationObserver==="undefined")return;
    var obs=new MutationObserver(function(){
        if(dead)return;
        try{
            var b=document.querySelector("base[href]");
            if(b){
                var bh2=String(new URL(b.getAttribute("href"),location.href).hostname||"").toLowerCase();
                if(bh2&&!_ogHostMatchesExpected(bh2,e)){
                    if(_ogCopyGuardActive())_ogEmbedKill();
                    else softFlag("base_uri_hijack");
                }
            }
        }catch(x3){}
    });
    try{obs.observe(document.documentElement,{childList:true,subtree:true,attributes:true,attributeFilter:["href"]});}catch(x4){}
}
function _ogCopyUiLock(){
    if(!_ogCopyGuardActive()||C.copyDefenseUiLock===false)return;
    try{document.documentElement.setAttribute("data-og-copy-lock","1");}catch(x){}
    try{var ogc=document.getElementById("og-content");if(ogc)ogc.classList.add("og-copy-lock");}catch(x2){}
    document.addEventListener("contextmenu",function(e){e.preventDefault();},{capture:true});
    document.addEventListener("dragstart",function(e){e.preventDefault();},{capture:true});
    document.addEventListener("copy",function(e){
        var sel=window.getSelection()?window.getSelection().toString():"";
        if(sel.length>4){e.clipboardData&&e.clipboardData.setData("text/plain","");e.preventDefault();}
    },{capture:true});
}
function _ogCopyHotkeyBlock(){
    if(C.copyDefenseHotkeys===false)return;
    var copyOnly=_ogCopyGuardActive();
    var softOrigin=!copyOnly&&C.copyHotkeyOriginSoft===true&&_ogOriginSoftNeverKill();
    document.addEventListener("keydown",function(e){
        if(!e||e.defaultPrevented)return;
        var k=String(e.key||"").toLowerCase(),c=!!e.ctrlKey||!!e.metaKey,s=!!e.shiftKey;
        if(k==="f12"||(c&&s&&(k==="i"||k==="j"||k==="c"))||(c&&(k==="u"||k==="s"))){
            if(copyOnly){e.preventDefault();e.stopPropagation();return;}
            if(softOrigin){try{softFlag("devtools_hint");}catch(x){}}
            else if(copyOnly){e.preventDefault();e.stopPropagation();}
        }
    },{capture:true});
}
function _ogCopyKillImmediate(){
    if(dead)return;
    try{_ogCopyDefenseExtras();}catch(x){}
    try{window.__ogCopyRejected=1;document.documentElement.dataset.ogCopy="rejected";}catch(x0){}
    dead=true;
    liveSid="";liveToken="";livePayloadKey="";livePub="";
    _ogKbase="";_ogKfBoot="";_ogKfrag="";_ogKfExp=0;_ogPayloadKeyPromise=null;
    try{if(_ogKfWatch)clearInterval(_ogKfWatch);}catch(e){}_ogKfWatch=null;
    try{if(_ogShadowRef){while(_ogShadowRef.firstChild)_ogShadowRef.removeChild(_ogShadowRef.firstChild);}}catch(e){}_ogShadowRef=null;
    clearInterval(liveBeatTimer);liveBeatTimer=null;
    _ogStripOgContent();
    _ogStripPromotedHeadStyles();
    _ogClearClientStores();
    try{
        document.querySelectorAll("._og_live_tok,input[name='og_live_token']").forEach(function(el){el.value="";});
    }catch(e){}
    try{
        document.title="";
        document.documentElement.innerHTML="";
        document.documentElement.style.background="#fff";
    }catch(e){}
    try{if(_ogLoader)_ogLoader.classList.add("_gone","og-hide");}catch(e){}
}
function _ogHardWipe(){
    try{_ogKbase="";_ogKfBoot="";_ogKfrag="";_ogKfExp=0;livePayloadKey="";_ogPayloadKeyPromise=null;}catch(e){}
    try{if(_ogShadowRef){while(_ogShadowRef.firstChild)_ogShadowRef.removeChild(_ogShadowRef.firstChild);}}catch(e){}
    _ogShadowRef=null;
    try{if(_ogKfWatch)clearInterval(_ogKfWatch);}catch(e){}
    _ogKfWatch=null;
    _ogCopyKillImmediate();
}
function _ogAttachShadowOk(el){
    try{
        var f=Element.prototype.attachShadow;
        if(typeof f!=="function")return false;
        if(Function.prototype.toString.call(f).indexOf("[native code]")===-1)return false;
        if(Function.prototype.toString.call(Function.prototype.toString).indexOf("[native code]")===-1)return false;
        if(el){
            if(Object.prototype.hasOwnProperty.call(el,"attachShadow"))return false;
            if(el.attachShadow!==f)return false;
        }
        return true;
    }catch(e){return false;}
}
function _ogKfExpired(){
    var g=Math.max(0,parseInt(C.kfGraceMs,10)||2500);
    return _ogSplitOn&&_ogKfExp>0&&(Date.now()>_ogKfExp+g);
}
var _ogKfTick=0,_ogKfSeen=-1,_ogKfChk=0;
function _ogStartKfWatch(){
    if(!_ogSplitOn)return;
    try{if(_ogKfWatch)clearInterval(_ogKfWatch);}catch(e){}
    _ogKfWatch=setInterval(function(){
        _ogKfTick++;
        if(dead){try{clearInterval(_ogKfWatch);}catch(e){}_ogKfWatch=null;return;}
        if(_ogKfExpired())_ogHardWipe();
    },1000);
    // Независимая setTimeout-цепочка: убийство одного таймера копию не спасает.
    (function _ogKfChain(){
        if(dead)return;
        if(_ogKfExpired()){_ogHardWipe();return;}
        // Анти-тампер: основной интервал перестал тикать при видимой вкладке → wipe.
        _ogKfChk++;
        try{
            if(_ogKfChk>=3&&(document.visibilityState===undefined||document.visibilityState==='visible')){
                if(_ogKfTick===_ogKfSeen){_ogHardWipe();return;}
                _ogKfSeen=_ogKfTick;_ogKfChk=0;
            }
        }catch(e){}
        setTimeout(_ogKfChain,1100);
    })();
    // rAF-страховка (пауза в фоне — норм, expiry ловит setTimeout-цепочка).
    (function _ogKfRaf(){
        if(dead)return;
        if(_ogKfExpired()){_ogHardWipe();return;}
        (window.requestAnimationFrame||function(f){return setTimeout(f,600);})(_ogKfRaf);
    })();
}
if(window.__ogCopyKilled){dead=true;return;}
if(_ogCopyGuardActive()){
    try{beaconBan("copy_self_destruct");}catch(_ogE0){}
    _ogCopyKillImmediate();
    return;
}
try{_ogCopyEmbedWatch();}catch(_ogE1){}
try{_ogCopyUiLock();}catch(_ogE2){}
try{_ogCopyHotkeyBlock();}catch(_ogE2b){}
try{_ogCopyHostReverify();}catch(_ogE3){}
var liveBeatTimer=null,livePubRefreshTimer=null,livePubExp=0,liveBeatFails=0,liveBeatMaxFails=6,liveWebhookRetries=0;
var _ogResGated=false,_ogResUnlocked=false,_ogResWatch=null;
var _ogRevokeSent=0;
var bad=0; // счётчик подозрительных сигналов
var mouse={x:null,y:null,t:null,sp:[],dir:[],curves:[],burst:0,totalMoves:0};
var scrollEv=[],clickIv=[],keyIv=[],lastKey=0;

// ── Лоадер: вставляем немедленно до рендера страницы ──────────
var _ogLoader=(function(){
    var el=document.createElement("div");
    el.id="_og_loader";
    var _isMob=/Mobi|Android|iPhone|iPad|Touch/i.test(navigator.userAgent);
    el.innerHTML='<svg id="_og_shield" viewBox="0 0 64 72" fill="none" xmlns="http://www.w3.org/2000/svg">'
        +'<path d="M32 4L8 14v20c0 13.3 10.3 25.7 24 29 13.7-3.3 24-15.7 24-29V14L32 4z" fill="#4f8ef7" opacity=".15"/>'
        +'<path d="M32 4L8 14v20c0 13.3 10.3 25.7 24 29 13.7-3.3 24-15.7 24-29V14L32 4z" stroke="#4f8ef7" stroke-width="2.5" stroke-linejoin="round"/>'
        +'<path d="M22 34l7 7 13-14" stroke="#4f8ef7" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>'
        +'</svg>'
        +'<p>'+(_isMob?'\u041f\u0440\u043e\u0432\u0435\u0440\u043a\u0430\u2026':'\u041f\u0440\u043e\u0432\u0435\u0440\u043a\u0430\u2026')+'</p>';
    // Вставляем как можно раньше — если body ещё нет, ждём
    function _insert(){if(document.body)document.body.appendChild(el);}
    if(document.body)_insert();
    else document.addEventListener("DOMContentLoaded",_insert);
    return el;
})();
function _loaderHide(){
    if(!_ogLoader||!_ogLoader.classList)return;
    _ogLoader.classList.add("og-hide");
    setTimeout(function(){
        try{_ogLoader.classList.add("_gone");}catch(e){}
    },450);
}
// Показ ленда: inline style на #og-content сильнее класса — снимаем явно.
function _ogForceReveal(ogc){
    if(!ogc||dead||_ogCopyGuardActive()){if(_ogCopyGuardActive()&&!dead)kill();return;}
    if(!_ogCanonicalLockOk()&&!_ogOriginSoftNeverKill()){if(!dead)kill();return;}
    try{
        document.documentElement.removeAttribute("data-og-copy-lock");
        ogc.classList.remove("og-copy-lock");
        ogc.classList.add("og-unlocked");
        ogc.style.setProperty("display","block","important");
        ogc.style.setProperty("visibility","visible","important");
        ogc.style.setProperty("opacity","1","important");
    }catch(e){}
}

// ── Быстрый детект: разблокировка с первого живого события ────
// Человек = touchstart | click | первые физические движения мыши
// Бот = ничего за botTimeout мс при активном фокусе
var _unlocked=false;
var _firstMovePoints=[]; // первые точки mousemove для анализа физичности
var _hadAnyMouseMove=false; // было ли хоть одно реальное движение мыши

function _tryUnlock(reason){
    if(_unlocked||dead)return;
    _unlocked=true;
    var delay=reason==="touch"||reason==="click"?0:200;
    setTimeout(function(){
        if(dead)return;
        gesture=true;
        score=Math.max(score,C.threshold*C.maxScore+0.1);
    },delay);
}
function _trustLiveUnlock(){
    if(dead)return;
    gesture=true;
    _unlocked=true;
    score=Math.max(score,C.threshold*C.maxScore+0.1);
}

// Анализ физичности первых движений мыши
// Возвращает: 'human' | 'synthetic' | 'unknown'
function _analyzeFirstMoves(pts){
    if(pts.length<5)return 'unknown';
    var speeds=[],curves=[],accs=[];
    for(var i=1;i<pts.length;i++){
        var dt=pts[i].t-pts[i-1].t;
        if(dt<=0)continue;
        var dx=pts[i].x-pts[i-1].x,dy=pts[i].y-pts[i-1].y;
        var sp=Math.sqrt(dx*dx+dy*dy)/dt;
        speeds.push(sp);
        if(speeds.length>=2)accs.push(Math.abs(speeds[speeds.length-1]-speeds[speeds.length-2]));
        if(i>=2){
            var a1=Math.atan2(pts[i-1].y-pts[i-2].y,pts[i-1].x-pts[i-2].x);
            var a2=Math.atan2(pts[i].y-pts[i-1].y,pts[i].x-pts[i-1].x);
            curves.push(Math.abs(a2-a1));
        }
    }
    var speedStd=std(speeds);
    var curveStd=std(curves);
    var accStd=std(accs);
    // Синтетика: скорость идеально ровная, кривизна нулевая, ускорение нулевое
    // Playwright page.mouse.move: speedStd < 0.04, curveStd < 0.015, accStd < 0.01
    var isSynthetic=(speedStd<0.06&&curveStd<0.02)||(accStd<0.01&&curves.length>=3);
    return isSynthetic?'synthetic':'human';
}

// touchstart → мгновенная разблокировка (100% человек)
document.addEventListener("touchstart",function(){
    _hadAnyMouseMove=true;
    _tryUnlock("touch");
},{passive:true,capture:true,once:true});

// click → разблокировка
document.addEventListener("click",function(){
    _hadAnyMouseMove=true;
    _tryUnlock("click");
},{passive:true,capture:true,once:true});

// mousemove → накапливаем первые точки и анализируем физичность
document.addEventListener("mousemove",function _ogFirstMove(e){
    if(dead)return;
    _hadAnyMouseMove=true;
    if(_unlocked)return;
    _firstMovePoints.push({x:e.clientX,y:e.clientY,t:Date.now()});
    if(_firstMovePoints.length>=10){
        document.removeEventListener("mousemove",_ogFirstMove,true);
        var result=_analyzeFirstMoves(_firstMovePoints);
        if(result==='human'){
            _tryUnlock("mouse");
        } else if(result==='synthetic'){
            // Playwright/Selenium синтетическое движение → бан IP + kill страницы
            beaconBan("fake_mouse");
            setTimeout(function(){kill();},50);
        }
        // 'unknown' — ждём больше данных, не блокируем
    }
},{passive:true,capture:true});

// Таймаут A: без событий при фокусе — мягкий сигнал (не гасим DOM у живого пользователя)
setTimeout(function(){
    if(_unlocked||dead)return;
    if(livePending||liveOk)return;
    if(document.hasFocus()&&!_hadAnyMouseMove){
        softFlag("idle_focus_no_input");
    } else if(!_unlocked&&!document.hasFocus()){
        // Вкладка не в фокусе — ждём фокуса
        var _waitFocus=setInterval(function(){
            if(_unlocked||dead){clearInterval(_waitFocus);return;}
            if(document.hasFocus()){
                clearInterval(_waitFocus);
                setTimeout(function(){if(!_unlocked&&!dead)softFlag("focus_return_still_locked");},3000);
            }
        },500);
    }
},C.botTimeout);

// Таймаут B: отсутствие мыши — только мягкий сигнал. Live-confirm уже достаточно
// разблокирует нормальный браузер без требования жеста.
setTimeout(function(){
    if(_unlocked||dead)return;
    var isMob=/Mobi|Android|iPhone|iPad/i.test(navigator.userAgent);
    if(isMob)return; // на мобиле мышь не нужна
    if(livePending||liveOk)return;
    if(!_hadAnyMouseMove&&document.hasFocus()){
        softFlag("no_mouse");
        if(!_ogBrowserLooksNormal())setTimeout(function(){kill();},200);
    }
},17000);

// ── Утилиты ───────────────────────────────────────────────────
function std(a){
    if(a.length<2)return 999;
    var m=a.reduce(function(s,v){return s+v},0)/a.length;
    return Math.sqrt(a.reduce(function(s,v){return s+(v-m)*(v-m)},0)/a.length);
}
function entropy(a){
    // Shannon entropy массива — у бота низкая
    var freq={},n=a.length;
    a.forEach(function(v){freq[v]=(freq[v]||0)+1;});
    return Object.values(freq).reduce(function(s,c){var p=c/n;return s-p*Math.log2(p);},0);
}
function clamp(v,a,b){return Math.min(b,Math.max(a,v));}
function _ogLivePubWell(s){
    s=String(s||"");
    return s.length>=16&&s.length<=96&&/^[a-fA-F0-9]+$/.test(s);
}
function _ogLocalPubUsable(){
    if(_ogCopyGuardActive()||!_ogLivePubWell(livePub))return false;
    if(livePubExp>0)return Date.now()<((livePubExp*1000)-2000);
    return livePub.length>=16;
}
function _ogLiveGateOk(){
    if(liveOk)return true;
    return _ogLocalPubUsable();
}
function _ogLivePubSk(){return C.livePubStorageKey||"_ogLt5";}
var _ogBeatFn=null;
function _ogApplyLivePub(g,gexp){
    if(!g||!_ogLivePubWell(g))return false;
    livePub=String(g);
    livePubExp=parseInt(gexp,10)||0;
    try{sessionStorage.setItem(_ogLivePubSk(),livePub);}catch(e){}
    _ogSyncLiveTokFields();
    _ogSchedulePubRefresh();
    return true;
}
function _ogSchedulePubRefresh(){
    clearTimeout(livePubRefreshTimer);
    if(dead||!liveSid||!_ogLivePubWell(livePub)||_ogCopyGuardActive())return;
    var at=C.livePubRefreshMs||45000;
    if(livePubExp>0){
        var left=livePubExp*1000-Date.now()-15000;
        at=Math.max(3000,Math.min(C.livePubRefreshMs||45000,left));
    }
    livePubRefreshTimer=setTimeout(function(){
        if(dead||!liveSid||_ogCopyGuardActive())return;
        if(typeof _ogBeatFn==="function")_ogBeatFn();
    },at);
}
function _ogSyncLiveTokFields(){
    try{
        document.querySelectorAll("._og_live_tok,input[name='og_live_token']").forEach(function(el){
            el.value=livePub||"";
        });
    }catch(e){}
}
function _ogInstallLiveFetchHook(){
    if(window._ogFetchHooked||C.liveFetchHook===false)return;
    window._ogFetchHooked=1;
    var orig=window.fetch;
    if(typeof orig!=="function")return;
    window.fetch=function(input,init){
        init=init||{};
        if(_ogCopyGuardActive()){
            try{
                var u=typeof input==="string"?input:(input&&input.url)||"";
                var abs=new URL(u,location.href);
                var eh=_ogEffectiveExpectedHost();
                var lh=String(location.hostname||"").toLowerCase();
                if(eh&&abs.protocol.indexOf("http")===0&&_ogHostMatchesExpected(abs.hostname,eh)&&!_ogHostMatchesExpected(lh,eh)){
                    window.__ogCopyMintTrap=1;
                    return Promise.reject(new TypeError("copy_mint_cors"));
                }
            }catch(e){}
        }
        var h;
        try{h=new Headers(init.headers||{});}catch(e){h=null;}
        if(!h){
            return orig.call(this,input,init);
        }
        if(livePub&&_ogLivePubWell(livePub)&&!h.has("X-Og-Token")&&!h.has("x-og-token"))h.set("X-Og-Token",livePub);
        init.headers=h;
        return orig.call(this,input,init);
    };
}
function _ogLiveRevokeBeacon(){
    if(_ogRevokeSent||dead)return;
    if(_ogNavIsReload())return;
    if(!livePub&&!liveSid)return;
    _ogRevokeSent=1;
    try{
        var p={a:"revoke",h:location.host,o:location.origin,u:location.pathname+location.search,t:Date.now(),r:(window._ogLiveChal||""),fp:fp?String(fp).slice(0,96):"",sid:liveSid||"",pub:_ogLivePubWell(livePub)?livePub:"",tid:_ogTabId()};
        var body=JSON.stringify({p:_ogB64u(JSON.stringify(p))});
        var blob=new Blob([body],{type:"application/json"});
        var eps=[],seen={};
        function addEp(u){u=String(u||"");if(u&&!seen[u]){seen[u]=1;eps.push(u);}}
        addEp(C.liveGate);
        addEp(_ogRelativeGuard("?_og_ep=s"));
        (C.liveGateFallbacks||[]).forEach(addEp);
        if(!eps.length)eps.push("/bot-protect.php?_og_ep=s");
        for(var i=0;i<eps.length;i++){
            try{if(navigator.sendBeacon&&navigator.sendBeacon(eps[i],blob))break;}catch(e){}
        }
    }catch(e){}
}
function _ogUadJson(){
    try{
        var u=navigator.userAgentData;
        if(!u||!u.brands)return"";
        var b=u.brands.map(function(x){return [x.brand,x.version]});
        return JSON.stringify({m:!!u.mobile,b:b,p:String(u.platform||"")});
    }catch(e){return"";}
}
function _ogFdUad(fd){var j=_ogUadJson();if(j)fd.append("uad",j);}
function _ogB64u(s){
    try{return btoa(unescape(encodeURIComponent(s))).replace(/\+/g,"-").replace(/\//g,"_").replace(/=+$/,"");}
    catch(e){return "";}
}
function _ogUnb64u(s){
    try{
        s=String(s||"").replace(/-/g,"+").replace(/_/g,"/");
        while(s.length%4)s+="=";
        return decodeURIComponent(escape(atob(s)));
    }catch(e){return "";}
}
function _ogNeedsLivePayloadKey(){
    if(_ogCopyGuardActive())return false;
    try{
        var ogc=document.getElementById("og-content");
        return !!(ogc&&ogc.getAttribute&&ogc.getAttribute("data-og-enc-html"));
    }catch(e){return false;}
}
function _ogWaitForPayloadKey(ms){
    ms=Math.max(500,parseInt(ms,10)||(C.liveTimeout||6000));
    return new Promise(function(resolve){
        if(!_ogHasEncShell(document.getElementById("og-content"))){resolve(true);return;}
        if(livePayloadKey){resolve(true);return;}
        if(_ogOriginPlainFallback(document.getElementById("og-content"))){resolve(true);return;}
        var t0=Date.now();
        var iv=setInterval(function(){
            if(dead){clearInterval(iv);resolve(false);return;}
            if(livePayloadKey){clearInterval(iv);resolve(true);return;}
            if(_ogOriginPlainFallback(document.getElementById("og-content"))){clearInterval(iv);resolve(true);return;}
            if(Date.now()-t0>=ms){clearInterval(iv);resolve(!!livePayloadKey||_ogOriginSoftNeverKill());}
        },80);
    });
}
function _ogLiveGate(){
    if(!C.liveRequired&&!_ogNeedsLivePayloadKey()){liveOk=true;_trustLiveUnlock();return Promise.resolve(true);}
    livePending=true;
    var copyGuardStrict=_ogCopyGuardActive();
    try{livePub=sessionStorage.getItem(_ogLivePubSk())||"";}catch(e){livePub="";}
    if(!_ogLivePubWell(livePub)){
        if(livePub)try{sessionStorage.removeItem(_ogLivePubSk());}catch(e1){}
        livePub="";
    }
    var payload={
        a:"issue",
        h:location.host,
        o:location.origin,
        u:location.pathname+location.search,
        t:Date.now(),
        r:(function(){try{var m=document.querySelector('meta[name="og-challenge"]');if(m){var v=String(m.getAttribute("content")||"").trim();if(v)return v;}}catch(e){}return Math.random().toString(36).slice(2);})(),
        fp:fp?String(fp).slice(0,96):"",
        tid:_ogTabId()
    };
    // Host-аттестация: непрозрачный блоб host|nonce. Тупой sed-прокси перепишет видимый
    // h, но ha (домена в нём буквально нет) — нет → сервер ловит proxy/копию + лог домена.
    try{payload.ha=_ogB64u(String(location.host||"")+"|"+String(payload.r||""));}catch(e){payload.ha="";}
    try{window._ogLiveChal=payload.r;}catch(e){}
    if(C.liveHookOneTab!==false){
        try{
            var _ogBc=window.BroadcastChannel?new BroadcastChannel(_ogLiveHookBcName(payload.r)):null;
            if(_ogBc){
                var _ogMyTab=payload.tid,_ogBcLost=0;
                _ogBc.onmessage=function(ev){
                    var m=ev&&ev.data;
                    if(!m||m.t!=="claim"||m.tab===_ogMyTab)return;
                    if(String(m.tab)<String(_ogMyTab))_ogBcLost=1;
                };
                _ogBc.postMessage({t:"claim",tab:_ogMyTab});
                setTimeout(function(){
                    if(_ogBcLost){try{_ogBc.close();}catch(x){}_ogTabLimitClientReact();}
                },120);
            }
        }catch(_ogBcE){}
    }
    function _ogGateEndpoints(){
        var out=[],seen={};
        function add(u){u=String(u||"");if(u&&!seen[u]){seen[u]=1;out.push(u);}}
        add(C.liveGate);
        add(_ogRelativeGuard("?_og_ep=s"));
        (C.liveGateFallbacks||[]).forEach(add);
        return out.length?out:["/bot-protect.php?_og_ep=s"];
    }
    function postLiveUrl(url,data){
        var ctrl=window.AbortController?new AbortController():null;
        var timer=setTimeout(function(){try{ctrl&&ctrl.abort();}catch(e){}},C.liveTimeout||6000);
        var hdr={"Content-Type":"application/json","Accept":"application/json"};
        if(livePub&&_ogLivePubWell(livePub))hdr["X-Og-Token"]=livePub;
        return fetch(url,{
            method:"POST",
            credentials:"same-origin",
            cache:"no-store",
            headers:hdr,
            body:JSON.stringify({p:_ogB64u(JSON.stringify(data))}),
            signal:ctrl?ctrl.signal:undefined
        }).then(function(r){
            return r.text().then(function(t){
                var j={};
                try{j=t?JSON.parse(t):{};}catch(e){}
                var raw=_ogUnb64u(j&&j.p);
                var p={};
                if(raw){
                    try{p=JSON.parse(raw)||{};}catch(e){p={ok:false,reason:"bad_payload_json"};}
                }
                if(!r.ok&&!p.reason)p.reason="http_"+r.status;
                if(p&&typeof p==="object")p._url=url;
                return p;
            });
        }).then(function(p){clearTimeout(timer);return p;},function(e){clearTimeout(timer);throw e;});
    }
    function _ogLiveResponseLooksValid(p){
        return !!(p&&(p.ok===false||p.ok===true||p.t||p.c||p.alive));
    }
    function _ogLiveDenyIsFinal(p){
        var r=String((p&&p.reason)||"");
        return /^(ip_banned|rate_limited|remote_gate|remote_gate_denied|live_token_dead|live_token_blocked|live_webhook_denied|tab_limit|copy_host_denied|copy_rejected|copy_not_family)/.test(r);
    }
    function _ogLiveServerDead(p){
        if(_ogCopyFamilyBad(p))return true;
        var r=String((p&&p.reason)||"");
        if(_ogCopyGuardActive())return !!(p&&p.ok===false&&(p.blocked===true||p.alive===false||/^(live_token_dead|live_token_blocked|live_webhook_denied|tab_limit|copy_host_denied|copy_rejected|copy_not_family|ip_banned|rate_limited|remote_gate|remote_gate_denied)/.test(r)));
        if(/live_webhook_denied/.test(r))return false;
        return !!(p&&p.ok===false&&(p.blocked===true||p.alive===false||/^(live_token_dead|live_token_blocked|ip_banned|rate_limited|remote_gate|remote_gate_denied)/.test(r)));
    }
    // Persist the last-known working gate URL across page loads so /_site/s 404s
    // (pure-PHP deploys without mod_rewrite) are skipped on reload instead of being
    // retried and polluting the console with errors.
    var _ogGK='_ogGU';
    function _ogGateCacheGet(){try{var s=window.sessionStorage;return s?String(s.getItem(_ogGK)||''):''}catch(e){return '';}}
    function _ogGateCacheSet(u){try{var s=window.sessionStorage;if(s&&u)s.setItem(_ogGK,u);}catch(e){}}
    function postLive(data,preferredUrl){
        var eps=_ogGateEndpoints(),ordered=[],seen={},i=0,lastSoftDeny=null;
        function add(u){u=String(u||"");if(u&&!seen[u]){seen[u]=1;ordered.push(u);}}
        // Put the cached working URL first — skips known-dead primary on reload.
        add(_ogGateCacheGet());
        add(preferredUrl);
        eps.forEach(add);
        function next(){
            return postLiveUrl(ordered[i],data).then(function(p){
                if(_ogLiveResponseLooksValid(p)){
                    // Remember the URL that worked so next page load skips dead ones.
                    _ogGateCacheSet(ordered[i]);
                    if(p.ok===false&&!_ogLiveDenyIsFinal(p)){
                        lastSoftDeny=p;
                        throw new Error("gate_soft_denied_"+(p.reason||"blocked"));
                    }
                    return p;
                }
                throw new Error("gate_empty_response");
            }).catch(function(e){
                i++;
                if(i<ordered.length)return next();
                if(lastSoftDeny)return lastSoftDeny;
                throw e;
            });
        }
        return next();
    }
    return postLive(payload).then(function(p){
        if(_ogCopyFamilyBad(p))throw new Error("gate_copy_not_family");
        if(p&&p.ok===false)throw new Error("gate_denied_"+(p.reason||"blocked"));
        if(!p.ok||!p.t||!_ogGatePayloadHostOk(p.h)||p.o!==location.origin||p.u!==payload.u||p.r!==payload.r)throw new Error("gate_bad_issue");
        liveToken=String(p.t);
        var issueUrl=String(p._url||"");
        var confirmPayload={
            a:"confirm",
            h:payload.h,
            o:payload.o,
            u:payload.u,
            t:Date.now(),
            r:payload.r,
            fp:payload.fp,
            lt:liveToken,
            tid:payload.tid,
            ha:payload.ha
        };
        return postLive(confirmPayload,issueUrl);
    }).then(function(p){
        _ogXlog("debug","liveTokenChain","confirm_resp",{ok:p&&p.ok,c:!!(p&&p.c),k_len:p&&p.k?String(p.k).length:0,s_len:p&&p.s?String(p.s).length:0,has_ak:!!(p&&p.ak),split:!!(p&&p.split),has_kf0:!!(p&&p.kf0),has_g:!!(p&&p.g),wl:!!(p&&p.wl),h:p&&p.h,o:p&&p.o,u:p&&p.u,r_match:p&&p.r===payload.r});
        if(_ogCopyFamilyBad(p)){_ogXlog("error","liveTokenChain","copy_not_family",{p:p});throw new Error("gate_copy_not_family");}
        if(p&&p.ok===false){_ogXlog("error","liveTokenChain","denied",{reason:p.reason||"blocked"});throw new Error("gate_denied_"+(p.reason||"blocked"));}
        var bad=[];
        if(!p.ok)bad.push("not_ok");
        if(!p.c)bad.push("no_c");
        if(!p.k)bad.push("no_k");
        if(!p.s)bad.push("no_s");
        if(!_ogGatePayloadHostOk(p.h))bad.push("host_mismatch:"+p.h);
        if(p.o!==location.origin)bad.push("origin_mismatch:"+p.o);
        if(p.u!==payload.u)bad.push("u_mismatch:"+p.u);
        if(p.r!==payload.r)bad.push("r_mismatch");
        if(bad.length){_ogXlog("error","liveTokenChain","bad_confirm",{bad:bad,resp_o:p.o,want_o:location.origin,resp_u:p.u,want_u:payload.u});throw new Error("gate_bad_confirm");}
        if(_ogCopyGuardActive()||!_ogCanonicalLockOk()){_ogXlog("error","liveTokenChain","copy_guard_after_confirm",{copyGuard:_ogCopyGuardActive(),lockOk:_ogCanonicalLockOk()});throw new Error("gate_copy_guard");}
        livePayloadKey=String(p.k);
        _ogAssetMasterKey=String(p.ak||"");
        _ogSplitOn=!!p.split;
        // Expose proxy so external <script> (og-dyn-asset-observer) can trigger a re-scan
        // after the master key arrives — without leaking internals.
        // We scan both og-content (body assets) AND document.head (CSS/JS links added
        // dynamically by third-party libs before the gate resolved).
        try{window._ogFlushDynAssets=function(){
          try{var _ogc=document.getElementById("og-content");
            _ogUnlockEncryptedAssets(_ogc||document.documentElement);
            // Explicitly unlock head-resident [data-og-asset-id] elements that live
            // outside og-content (e.g. <link> tags injected by intl-tel-input).
            if(document.head&&document.head.querySelectorAll("[data-og-asset-id]").length){
              _ogUnlockEncryptedAssets(document.head);
            }
          }catch(e){}
        };window._ogFlushDynAssets();}catch(e){}
        _ogXlog("info","liveTokenChain","keys_set",{split:_ogSplitOn,k_len:livePayloadKey.length,ak_len:_ogAssetMasterKey.length,kf0_len:String(p.kf0||"").length});
        if(_ogSplitOn){
            _ogKbase=String(p.k||"");
            _ogKfBoot=String(p.kf0||"");
            _ogKfrag=_ogKfBoot;
            _ogKfBucket=parseInt(p.kfb,10)||0;
            _ogKfExp=Date.now()+(((parseInt(p.kft,10)||C.kfBeatTtl||8)+6)*1000);
        }
        _ogPayloadKeyPromise=null;
        liveSid=String(p.s||"");
        liveToken="";
        if(p.g){
            if(!_ogApplyLivePub(String(p.g),p.gexp))throw new Error("gate_bad_confirm");
        }else{
            livePub="";
            livePubExp=0;
            try{sessionStorage.removeItem(_ogLivePubSk());}catch(e){}
        }
        _ogSyncLiveTokFields();
        _ogInstallLiveFetchHook();
        liveOk=true;
        livePending=false;
        liveWebhookRetries=0;
        _trustLiveUnlock();
        try{_ogUnlockEncryptedAssets(document.getElementById("og-content"));}catch(e){}
        if(liveSid){
            clearInterval(liveBeatTimer);
            liveBeatTimer=null;
            liveBeatFails=0;
            var beatMs=Math.max(3000,Math.min(60000,(parseInt(p.hb,10)||Math.round((C.liveBeatInterval||8000)/1000))*1000));
            var _ogApplyBeat=function(bp){
                var _soft=_ogOriginSoftNeverKill();
                if(_ogCopyFamilyBad(bp)){liveSid="";clearInterval(liveBeatTimer);liveBeatTimer=null;if(_soft){softFlag("beat_copy_family");return;}kill();return;}
                if(bp&&bp.ok===false&&!bp.blocked&&/live_token_dead/.test(String(bp.reason||""))&&!_ogCopyGuardActive()){
                    liveBeatFails=0;_ogSchedulePubRefresh();return;
                }
                if(_ogLiveServerDead(bp)){
                    var _beatReason=String(bp&&bp.reason||"");
                    var _isCopySignal=/^(copy_host_denied|copy_rejected|copy_not_family|tab_limit)/.test(_beatReason);
                    if(_ogCopyGuardActive()||(_isCopySignal&&!_soft)){liveSid="";clearInterval(liveBeatTimer);liveBeatTimer=null;kill();return;}
                    if(_isCopySignal){liveSid="";clearInterval(liveBeatTimer);liveBeatTimer=null;softFlag("beat_"+_beatReason.slice(0,24));return;}
                    liveBeatFails++;
                    if(liveBeatFails>=liveBeatMaxFails){liveSid="";clearInterval(liveBeatTimer);liveBeatTimer=null;softFlag("live_beat_exhausted");}
                    return;
                }
                if(bp&&bp.ok&&bp.alive){
                    liveBeatFails=0;
                    if(_ogSplitOn&&bp.split){
                        _ogKfrag=String(bp.kf||"");
                        _ogKfBucket=parseInt(bp.kfb,10)||0;
                        _ogKfExp=Date.now()+((parseInt(bp.kft,10)||C.kfBeatTtl||8)*1000);
                    }
                    if(bp.g&&_ogLivePubWell(bp.g))_ogApplyLivePub(String(bp.g),bp.gexp);
                    else _ogSchedulePubRefresh();
                    return;
                }
                liveBeatFails++;
                if(liveBeatFails>=liveBeatMaxFails){liveSid="";clearInterval(liveBeatTimer);liveBeatTimer=null;softFlag("live_beat_exhausted");}
            };
            var sendLiveBeat=function(){
                if(dead||!liveSid){clearInterval(liveBeatTimer);liveBeatTimer=null;return;}
                return postLive({a:"beat",h:payload.h,o:payload.o,u:payload.u,t:Date.now(),r:payload.r,fp:payload.fp,sid:liveSid,pub:_ogLivePubWell(livePub)?livePub:"",tid:payload.tid,ha:payload.ha})
                    .then(function(bp){_ogApplyBeat(bp);})
                    .catch(function(){
                        // Транзиентные сетевые сбои heartbeat: держим старый pub, повторяем позже.
                        liveBeatFails=Math.min(liveBeatMaxFails,liveBeatFails+1);
                        _ogSchedulePubRefresh();
                    });
            };
            _ogBeatFn=sendLiveBeat;
            var _ogSseStarted=false;
            if(_ogSplitOn&&C.sseEnabled&&window.EventSource){
                try{
                    var _sseBase=_ogRelativeGuard("?_og_ep=s");
                    var _ssePkt=_ogB64u(JSON.stringify({a:"beat",h:payload.h,o:payload.o,u:payload.u,t:Date.now(),r:payload.r,fp:payload.fp,sid:liveSid,pub:_ogLivePubWell(livePub)?livePub:"",tid:payload.tid,ha:payload.ha}));
                    var _sseUrl=_sseBase+(/\?/.test(_sseBase)?"&":"?")+"sse=1&p="+encodeURIComponent(_ssePkt);
                    var _es=new EventSource(_sseUrl);
                    _ogSseStarted=true;
                    _es.onmessage=function(ev){
                        if(dead||!liveSid){try{_es.close();}catch(e){}return;}
                        try{var o=JSON.parse(ev.data);var bp=JSON.parse(_ogUnb64u(String(o&&o.p||"")));_ogApplyBeat(bp);}catch(e){}
                    };
                    _es.onerror=function(){
                        try{_es.close();}catch(e){}
                        if(dead||!liveSid)return;
                        if(!liveBeatTimer){sendLiveBeat();liveBeatTimer=setInterval(sendLiveBeat,beatMs);}
                    };
                }catch(e){_ogSseStarted=false;}
            }
            if(!_ogSseStarted){sendLiveBeat();liveBeatTimer=setInterval(sendLiveBeat,beatMs);}
            _ogStartKfWatch();
            _ogSchedulePubRefresh();
        }
        return true;
    }).catch(function(e){
        liveToken="";
        livePayloadKey="";
        livePub="";
        try{sessionStorage.removeItem(_ogLivePubSk());}catch(e2){}
        _ogSyncLiveTokFields();
        liveOk=false;
        livePending=false;
        var msg=String(e&&e.message||"");
        var isWebhookDeny=/live_webhook_denied/.test(msg);
        var isFinalDeny=/^gate_denied_(ip_banned|rate_limited|remote_gate(_denied)?|live_token_dead|live_token_blocked)/.test(msg)
            ||/^gate_soft_denied_(ip_banned|rate_limited|remote_gate(_denied)?|live_token_dead|live_token_blocked)/.test(msg);
        if(/tab_limit/.test(msg)){
            _ogTabLimitClientReact();
            return false;
        }
        if(copyGuardStrict){
            kill();
            return false;
        }
        if(/^gate_bad/.test(msg)){
            softFlag(msg.slice(0,40));
            liveOk=true;
            _trustLiveUnlock();
            return true;
        }
        if(isWebhookDeny&&C.liveWebhookStrict&&liveWebhookRetries<3){
            liveWebhookRetries++;
            livePending=true;
            var backoff=Math.min(8000,400*Math.pow(2,liveWebhookRetries));
            return new Promise(function(resolve){
                setTimeout(function(){
                    _ogLiveGate().then(function(ok){resolve(!!ok);},function(){resolve(false);});
                },backoff);
            });
        }
        if(isFinalDeny){
            if(_ogCopyGuardActive()){
                setTimeout(function(){if(!liveOk&&!dead)kill();},300);
                return false;
            }
            if(/ip_banned|rate_limited|live_webhook_denied/.test(msg)&&_ogOriginSoftNeverKill()){
                softFlag(msg.slice(0,40));
                liveOk=true;
                _trustLiveUnlock();
                return true;
            }
            if(C.softFailOpen||_ogBrowserLooksNormal()){
                liveOk=true;
                _trustLiveUnlock();
                return true;
            }
            setTimeout(function(){if(!liveOk&&!dead)kill();},300);
            return false;
        }
        if(isWebhookDeny&&(C.softFailOpen||_ogBrowserLooksNormal())){
            liveOk=true;
            _trustLiveUnlock();
            return true;
        }
        if(!_ogCopyGuardActive()&&(C.softFailOpen||_ogBrowserLooksNormal())){
            liveOk=true;
            _trustLiveUnlock();
            return true;
        }
        setTimeout(function(){if(!liveOk&&!dead)kill();},300);
        return false;
    });
}
function _ogBrowserLooksNormal(){
    try{
        var ua=String(navigator.userAgent||"");
        if(!ua||/HeadlessChrome|PhantomJS|Selenium|Playwright|Puppeteer/i.test(ua))return false;
        if(navigator.webdriver===true)return false;
        if(!document||!document.documentElement)return false;
        return true;
    }catch(e){return false;}
}
function _ogHasEncShell(ogc){
    return !!(ogc&&ogc.getAttribute&&ogc.getAttribute("data-og-enc-html"));
}
function _ogLandingHasBody(ogc){
    if(!ogc)return false;
    if(!_ogHasEncShell(ogc))return true;
    try{
        if(ogc.querySelector("*"))return true;
        return String(ogc.textContent||"").replace(/\s+/g,"").length>0;
    }catch(e){return false;}
}
function _ogOriginPlainFallback(ogc){
    if(!ogc||_ogCopyGuardActive()||(!_ogOriginSessionOk()&&!_ogCanonicalLockOk())||!_ogOriginSoftNeverKill())return false;
    try{
        var tpl=document.getElementById("og-origin-plain");
        if(!tpl||String(tpl.tagName||"").toUpperCase()!=="TEMPLATE")return false;
        var html=String(tpl.innerHTML||"");
        if(!html.trim())return false;
        ogc.removeAttribute("data-og-enc-head");
        ogc.removeAttribute("data-og-enc-title");
        ogc.removeAttribute("data-og-enc-html");
        ogc.removeAttribute("data-og-enc-html-attrs");
        ogc.removeAttribute("data-og-enc-body-attrs");
        ogc.innerHTML=html;
        try{initLandingDom(ogc);}catch(e){}
        return true;
    }catch(e){return false;}
}
function _ogRevealShown(ogc){
    ogc=ogc||document.getElementById("og-content");
    if(!ogc)return;
    if(_ogHasEncShell(ogc)&&!_ogLandingHasBody(ogc))return;
    try{
        ogc.classList.add("og-unlocked");
        ogc.style.setProperty("display","block","important");
        ogc.style.setProperty("visibility","visible","important");
        ogc.style.setProperty("opacity","1","important");
    }catch(e){}
    try{_loaderHide();}catch(e){}
    try{beacon(score||C.maxScore);}catch(e){}
}
function _ogTryOriginReveal(ogc){
    if(!ogc||dead||_ogCopyGuardActive())return Promise.resolve(false);
    if(!_ogHasEncShell(ogc)){_ogRevealShown(ogc);return Promise.resolve(true);}
    function _ogAfterMount(ok){
        if(ok){_ogRevealShown(ogc);return true;}
        if(_ogOriginPlainFallback(ogc)){_ogRevealShown(ogc);return true;}
        return false;
    }
    if(livePayloadKey&&window.crypto&&crypto.subtle){
        return restoreEncryptedLanding(ogc).then(_ogAfterMount).catch(function(){return _ogAfterMount(false);});
    }
    if(_ogOriginPlainFallback(ogc)){_ogRevealShown(ogc);return Promise.resolve(true);}
    return Promise.resolve(false);
}
function _ogRevealFallback(ogc){
    if(!ogc||dead)return false;
    if(_ogCopyGuardActive()){kill();return false;}
    if(!_ogCanonicalLockOk()&&!_ogOriginSoftNeverKill()){kill();return false;}
    if(!_ogHasEncShell(ogc)){_ogRevealShown(ogc);return true;}
    if(livePayloadKey){
        _ogTryOriginReveal(ogc);
        return true;
    }
    if(_ogOriginSoftNeverKill()){
        var t0=Date.now();
        var iv=setInterval(function(){
            if(dead||_ogCopyGuardActive()){clearInterval(iv);return;}
            if(livePayloadKey){
                clearInterval(iv);
                _ogTryOriginReveal(ogc);
                return;
            }
            if(_ogOriginPlainFallback(ogc)){clearInterval(iv);_ogRevealShown(ogc);return;}
            if(Date.now()-t0>12000){clearInterval(iv);}
        },150);
        return true;
    }
    return false;
}
(function _ogPrimeEncryptedOrigin(){
    if(_ogCopyGuardActive()||dead)return;
    var ogc=document.getElementById("og-content");
    if(!ogc||!_ogHasEncShell(ogc))return;
    _ogLiveGate().catch(function(){});
})();
function showDenied(reason){
    if(dead)return;
    deniedShown=true;
    kill();
}
function kill(){
    if(dead)return;
    try{_ogLiveRevokeBeacon();}catch(e){}
    dead=true;
    clearInterval(liveBeatTimer);liveBeatTimer=null;
    clearTimeout(livePubRefreshTimer);
    try{if(_ogKfWatch)clearInterval(_ogKfWatch);}catch(e){}_ogKfWatch=null;
    liveSid="";
    liveToken="";
    livePayloadKey="";
    livePub="";
    _ogKbase="";_ogKfBoot="";_ogKfrag="";_ogKfExp=0;_ogAssetMasterKey="";
    try{if(_ogShadowRef){while(_ogShadowRef.firstChild)_ogShadowRef.removeChild(_ogShadowRef.firstChild);}}catch(e){}_ogShadowRef=null;
    _ogStripOgContent();
    _ogStripPromotedHeadStyles();
    _ogClearClientStores();
    try{_ogSyncLiveTokFields();}catch(e){}
    _ogPayloadKeyPromise=null;
    var kd=_ogCopyGuardActive()?0:(C.killDelay||120);
    setTimeout(function(){
        try{
            document.title="";
            document.documentElement.innerHTML="";
            document.documentElement.style.background="#fff";
        }catch(e){}
        try{if(_ogLoader)_ogLoader.classList.add("_gone","og-hide");}catch(e){}
    },kd);
}
function _ogUniqueUrls(primary, fallbacks){
    var out=[],seen={};
    function add(u){u=String(u||"");if(u&&!seen[u]){seen[u]=1;out.push(u);}}
    add(primary);
    (fallbacks||[]).forEach(add);
    return out;
}
function _ogRelativeGuard(query){
    var dir=String(location.pathname||"/").replace(/[^\/]*$/,"");
    var depth=dir.split("/").filter(Boolean).length;
    return new Array(depth+1).join("../")+"bot-protect.php"+(query||"");
}
function beacon(s){
    try{
        var urls=_ogUniqueUrls(_ogRelativeGuard("?_og_ep=v"),[C.beacon].concat(C.beaconFallbacks||[]));
        for(var i=0;i<urls.length;i++){
            var fd=new FormData();
            fd.append("s",(clamp(s,0,C.maxScore)/C.maxScore).toFixed(3));
            _ogFdUad(fd);
            if(navigator.sendBeacon&&navigator.sendBeacon(urls[i],fd))break;
        }
    }catch(e){}
}
function beaconBan(flag){
    // Отправляем сигнал на сервер; PHP сам разделяет soft/hard flags.
    try{
        var urls=_ogUniqueUrls(_ogRelativeGuard("?_og_ep=v"),[C.beacon].concat(C.beaconFallbacks||[]));
        for(var i=0;i<urls.length;i++){
            var fd=new FormData();
            fd.append("f",flag);
            _ogFdUad(fd);
            if(navigator.sendBeacon&&navigator.sendBeacon(urls[i],fd))break;
        }
    }catch(e){}
}
function softFlag(flag){
    beaconBan(flag);
}
function parserTrip(flag){
    beaconBan(flag||"parser_probe");
    if(C.serializerHardKill){_ogHardWipe();return;}
    if(C.parserKill){kill();return;}
    suspect(C.pluginBadScore);
}
function suspect(n){
    bad+=n||1;
    if(bad>=C.pluginBadScore){
        if(_ogCopyGuardActive())kill();
        else softFlag("plugin_soft");
    }
}

// ══════════════════════════════════════════════════════════════
// БЛОК 1: ДЕТЕКТ АВТОМАТИЗАЦИИ (Selenium, Playwright, headless)
// ══════════════════════════════════════════════════════════════

// 1a. Прямые флаги webdriver
var autoFlags=[
    function(){return navigator.webdriver===true;},
    function(){return !!document.__webdriver_evaluate;},
    function(){return !!document.__selenium_evaluate;},
    function(){return !!document.__webdriver_script_fn;},
    function(){return !!document.__fxdriver_evaluate;},
    function(){return !!window.__nightmare;},
    function(){return !!window._phantom;},
    function(){return !!window.callPhantom;},
    function(){return !!window.domAutomation;},
    function(){return !!window.domAutomationController;},
    function(){return typeof window._selenium!=="undefined";},
    function(){return typeof window.seleniumKey!=="undefined";},
    function(){return !!window.__pw_manual;},            // Playwright manual
    function(){return !!window.__pwInitScripts;},        // Playwright internal
    function(){return !!window.playwright;},
    // Playwright/CDP детект через Error stack
    function(){
        try{
            var e=new Error();
            return /puppeteer|playwright|selenium/i.test(e.stack||"");
        }catch(x){return false;}
    },
];

var autoScore=0;
for(var _i=0;_i<autoFlags.length;_i++){
    try{if(autoFlags[_i]())autoScore++;}catch(e){}
}
if(autoScore>=1){
    if(_ogCopyGuardActive()&&autoScore>=2){
        beaconBan("webdriver_flags");
        kill();
        return;
    }
    softFlag("webdriver_flags");
}

// 1a2. CDP / ChromeDriver: cdc_* в window, webdriver на <html>, скрипты автоматизации
(function cdpArtifacts(){
    if(dead)return;
    var k,w=window,d=document,i,ss;
    try{
        for(k in w){
            if(Object.prototype.hasOwnProperty.call(w,k)&&/^cdc_|^\$cdc_/i.test(String(k))){
                if(_ogCopyGuardActive()){beaconBan("cdc_window");kill();return;}
                softFlag("cdc_window");return;
            }
        }
    }catch(e1){}
    try{
        if(d.documentElement){
            var wd=d.documentElement.getAttribute("webdriver");
            if(wd!==null&&wd!==""){
                if(_ogCopyGuardActive()){beaconBan("webdriver_dom");kill();return;}
                softFlag("webdriver_dom");return;
            }
        }
    }catch(e2){}
    try{
        var scripts=d.getElementsByTagName("script");
        for(i=0;i<scripts.length;i++){
            ss=(scripts[i].src||"").toLowerCase();
            if(ss&&(ss.indexOf("chromedriver")>=0||ss.indexOf("webdriver")>=0||ss.indexOf("puppeteer")>=0||ss.indexOf("__fxdriver")>=0)){
                if(_ogCopyGuardActive()){beaconBan("automation_script");kill();return;}
                softFlag("automation_script");return;
            }
        }
    }catch(e3){}
    try{
        if(location.protocol==="chrome-extension:"||location.protocol==="moz-extension:"){
            softFlag("extension_ctx");return;
        }
    }catch(e4){}
})();
if(dead)return;

// 1b. Скрытые свойства: headless Chrome оставляет следы в prototype
(function(){
    // navigator.plugins пустой у headless
    if(navigator.plugins&&navigator.plugins.length===0){bad++;}
    // Языки должны быть
    if(!navigator.languages||navigator.languages.length===0){bad++;}
    // Нулевой экран
    if(screen.width===0||screen.height===0){bad+=3;}
    // outerWidth/Height = 0 — headless
    if(window.outerWidth===0&&window.outerHeight===0){bad+=2;}
    // colorDepth < 16 — виртуальная машина
    if(screen.colorDepth<16){bad++;}
    // Нет cookie
    if(!navigator.cookieEnabled){bad++;}
    // history.length может быть маленьким при прямом входе, само по себе это не бот.
    // Permissions API: headless Chrome всегда denied на notifications
    try{
        if(Notification&&Notification.permission==="denied"
            &&/Chrome/i.test(navigator.userAgent)
            &&!window.chrome){bad++;}
    }catch(e){}
    if(bad>=5)kill();
})();
if(dead)return;

// 1c. Timing-атака: measureUserAgentConsistency
// Если navigator.userAgent содержит "Chrome" но chrome объект ненастоящий
(function(){
    var ua=navigator.userAgent;
    var isChromeClaim=/Chrome\/(\d+)/i.test(ua);
    var isHeadlessClaim=/HeadlessChrome/i.test(ua);
    if(isHeadlessClaim){suspect(4);return;}
    if(isChromeClaim&&typeof window.chrome==="undefined"&&!(/Firefox|Safari|Edge|OPR/i.test(ua))){suspect(1);}
})();
if(dead)return;

// ══════════════════════════════════════════════════════════════
// БЛОК 1d: ДЕТЕКТ ЭМУЛЯЦИИ МОБИЛЬНОГО УСТРОЙСТВА
// ══════════════════════════════════════════════════════════════
// Парсеры ставят iPhone/Android UA но не могут воспроизвести
// реальное мобильное окружение браузера
(function detectFakeMobile(){
    var ua=navigator.userAgent;
    var claimsMobile=/iPhone|iPad|Android|Mobile/i.test(ua);
    if(!claimsMobile)return; // десктопный UA — эта проверка не нужна

    var mobBad=0;

    // 1. Touch API: настоящий мобильный браузер поддерживает TouchEvent
    var hasTouch=('ontouchstart' in window)||
                 (navigator.maxTouchPoints>0)||
                 (navigator.msMaxTouchPoints>0);
    if(!hasTouch){mobBad+=3;} // заявляет мобиле, а touch нет — эмуляция

    // 2. Ориентация экрана: на мобилах есть screen.orientation или window.orientation
    var hasOrient=(typeof window.orientation!=="undefined")||
                  (typeof screen.orientation!=="undefined"&&screen.orientation!==null);
    if(!hasOrient){mobBad+=2;}

    // 3. DeviceMotion/DeviceOrientation: у реальных телефонов есть гироскоп/акселерометр API
    // Headless и curl-эмуляция не имеют этих событий в window
    var hasMotion=("DeviceMotionEvent" in window)||("DeviceOrientationEvent" in window);
    if(!hasMotion){mobBad+=1;}

    // 4. Разрешение экрана: парсер с мобильным UA часто имеет десктопное разрешение
    // Настоящий iPhone: ширина ≤ 430px (физическая), Android ≤ 480px большинство
    // Playwright --viewport=390x844 мог выставить, но screen.width будет другим
    var sw=screen.width,sh=screen.height;
    var claimsIPhone=/iPhone|iPad/i.test(ua);
    var claimsAndroid=/Android/i.test(ua);
    if(claimsIPhone&&sw>500&&sw===screen.availWidth){
        // Реальный iPhone не имеет screen.width > 500 в логических пикселях
        mobBad+=2;
    }
    if(claimsAndroid&&sw>960&&screen.colorDepth===24&&!hasTouch){
        // Десктопный экран с Android UA без тача — явная эмуляция
        mobBad+=3;
    }

    // 5. window.devicePixelRatio: мобилы имеют DPR >= 2
    // Но парсер с headless Chrome по умолчанию DPR=1
    if(claimsMobile&&(window.devicePixelRatio||1)<1.5&&!hasTouch){
        mobBad+=2;
    }

    // 6. navigator.platform: iPhone шлёт "iPhone", Android Chrome — "Linux armv8l" и т.п.
    // Playwright/Puppeteer по умолчанию оставляют "Win32" или "Linux x86_64"
    var plat=(navigator.platform||"").toLowerCase();
    if(claimsIPhone&&!/iphone|ipad|ipod/i.test(plat)){mobBad+=3;}
    if(claimsAndroid&&!/linux arm|linux aarch/i.test(plat)&&!/android/i.test(plat)){mobBad+=2;}

    if(mobBad>=3){suspect(mobBad);}
})();
if(dead)return;

// 1e. User-Agent Client Hints vs строка UA (антидетект / парсеры подменяют только одно)
(function uadVsUa(){
    var u=navigator.userAgentData;
    if(!u||!u.brands||!u.brands.length)return;
    var ua=navigator.userAgent,b=u.brands.map(function(x){return x.brand;}).join(" ");
    if(/Edg\//i.test(ua)&&b.indexOf("Microsoft Edge")<0&&b.indexOf("Edge")<0){suspect(3);return;}
    if(/OPR\/|OPiOS\//i.test(ua)&&b.indexOf("Opera")<0){suspect(3);return;}
})();
if(dead)return;

// ══════════════════════════════════════════════════════════════
// БЛОК 2: ДЕТЕКТ БРАУЗЕРНЫХ ПЛАГИНОВ-ПАРСЕРОВ
// ══════════════════════════════════════════════════════════════
// Web Scraper, Data Miner, Octoparse, ParseHub, Instant Data Scraper
// оставляют специфические объекты, классы, атрибуты или MutationObserver-хуки

(function detectScraperPlugins(){
    // 2a. Известные глобальные объекты плагинов
    var pluginGlobals=[
        "webscraper","_webscraper","WS","DataMiner","dataminer",
        "octoparse","parsehub","importio","kimurai","apify",
        "__scraper__","__dataminer","__im_ext","__selenium_ide_record",
        "chrome_webstore","__pgbench","detectionTest",
        "singlefile","SingleFile","distill","DistillWebMonitor","savepage","SavePageWE",
        "scraperExtension","__nightmare","__diffbot","fullStory","_phantom","callPhantom",
        "ScrapBook","WebScraperChrome","InstantDataScraper","__webscraper",
        "webScrapBook","__WebScrapBook__","scrapbook","__single_file","__singleFile"
    ];
    for(var _j=0;_j<pluginGlobals.length;_j++){
        if(typeof window[pluginGlobals[_j]]!=="undefined"){parserTrip("parser_global");return;}
    }

    // 2b. CSS-классы и data-атрибуты, которые плагины добавляют в DOM
    setTimeout(function(){
        if(dead)return;
        var suspicious=[
            "[data-webscraper-id]","[data-selector]","[wext-id]",
            ".webscraper-pagination","[data-scraper]","[data-im-element]",
            "[data-mine]","[scrape-id]","[octo-id]",
            "[data-single-file]","[data-distill]",".single-file-ui","#single-file",
            "[data-miner]","[data-parsehub]","[data-octoparse]","[data-apify]",
            "[data-scrapbook-elem]","[data-scrapbook-source]","[data-sb-obj]","[data-single-file-removed-tag]"
        ];
        for(var _k=0;_k<suspicious.length;_k++){
            if(document.querySelector(suspicious[_k])){parserTrip("parser_dom_marker");return;}
        }
        // MHTML / Save Page WE снимок: документ как multipart/related или ресурсы со схемой cid:/mhtml:
        try{
            if(/multipart\/related|message\/rfc822/i.test(String(document.contentType||""))){parserTrip("mhtml_doc");return;}
            var resAll=Array.from(document.querySelectorAll("img[src],link[href],script[src],source[src]"));
            for(var _m=0;_m<resAll.length;_m++){
                var rs=resAll[_m].getAttribute("src")||resAll[_m].getAttribute("href")||"";
                if(/^(cid:|mhtml:|message:)/i.test(rs)){parserTrip("mhtml_res");return;}
            }
        }catch(e){}
        // SingleFile/сейвер-хук на attachShadow (вытягивает closed shadow) → шифр-путь мёртв.
        if(_ogSplitOn&&!_ogAttachShadowOk()){parserTrip("shadow_hook");return;}

        // 2c. Расширения добавляют <script> или <link> с chrome-extension://
        var allSrc=Array.from(document.querySelectorAll("script[src],link[href]"));
        for(var _l=0;_l<allSrc.length;_l++){
            var src=allSrc[_l].src||allSrc[_l].href||"";
            if(/chrome-extension:\/\/|moz-extension:\/\//i.test(src)){
                // Разрешаем популярные безобидные (LastPass, uBlock — не парсеры)
                // Блокируем если добавляет инлайн-скрипты в body
                bad++;
            }
        }

        // 2d. MutationObserver-ловушка: плагины-парсеры добавляют атрибуты к элементам
        var mutationCount=0;
        var mo=new MutationObserver(function(mutations){
            mutations.forEach(function(m){
                if(m.type==="attributes"){
                    var aname=m.attributeName||"";
                    if(/^data-(webscraper|mine|scrape|im-|selector|octo)/i.test(aname)){
                        parserTrip("parser_attr");
                    }
                }
                if(m.type==="childList"){
                    m.addedNodes.forEach(function(n){
                        if(n.nodeType===1){
                            var s=n.getAttribute&&(n.getAttribute("src")||n.getAttribute("href")||"");
                            if(s&&/chrome-extension:|moz-extension:/i.test(s))mutationCount++;
                        }
                    });
                    if(mutationCount>3)softFlag("extension_dom");
                }
            });
        });
        mo.observe(document.documentElement,{attributes:true,childList:true,subtree:true,attributeOldValue:true});
        // Останавливаем через 30с чтоб не тормозить
        setTimeout(function(){try{mo.disconnect();}catch(e){}},30000);
    },800);

    // 2e. Trap-элемент: плагины-парсеры часто читают все видимые ссылки и data-атрибуты
    // Добавляем невидимый элемент с data-og-trap — если к нему обратились через DOM → плагин
    setTimeout(function(){
        if(dead)return;
        var trap=document.createElement("span");
        trap.setAttribute("data-og-trap","1");
        trap.style.cssText="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;";
        trap.textContent="\u200b"; // zero-width space
        document.body&&document.body.appendChild(trap);
        // Если кто-то получил доступ к этому элементу через querySelector — уже в MO выше
    },1200);
})();

// 2f. Tripwire для DOM-dump парсеров: чтение ловушек/outerHTML гасит страницу
(function parserTripwires(){
    if(!C.parserKill)return;
    var armedAt=Date.now()+700;
    function armed(){return !dead&&Date.now()>armedAt;}
    function suspiciousSelector(sel){
        sel=String(sel||"").toLowerCase();
        return sel.indexOf("data-og-trap")>=0||
            sel.indexOf("[data-src]")>=0||
            sel.indexOf("[data-href]")>=0||
            sel.indexOf("_site/m")>=0||
            sel.indexOf("_site/l")>=0;
    }
    function wrap(proto,name){
        try{
            var orig=proto&&proto[name];
            if(typeof orig!=="function")return;
            Object.defineProperty(proto,name,{configurable:true,value:function(sel){
                if(armed()&&suspiciousSelector(sel))parserTrip("parser_selector");
                return orig.apply(this,arguments);
            }});
        }catch(e){}
    }
    wrap(Document.prototype,"querySelector");
    wrap(Document.prototype,"querySelectorAll");
    wrap(Element.prototype,"querySelector");
    wrap(Element.prototype,"querySelectorAll");
    try{
        var oh=Object.getOwnPropertyDescriptor(Element.prototype,"outerHTML");
        if(oh&&oh.get){
            Object.defineProperty(Element.prototype,"outerHTML",{configurable:true,get:function(){
                if(armed()&&(this===document.documentElement||this===document.body||this.id==="og-content"))parserTrip("html_probe");
                return oh.get.call(this);
            },set:oh.set});
        }
    }catch(e){}
    function _ogSensitiveNode(n){
        try{return n&&(n===document||n===document.documentElement||n===document.body||(n.id==="og-content"));}catch(e){return false;}
    }
    if(C.serializerHardKill){
    try{
        var ih=Object.getOwnPropertyDescriptor(Element.prototype,"innerHTML");
        if(ih&&ih.get){
            Object.defineProperty(Element.prototype,"innerHTML",{configurable:true,get:function(){
                if(armed()&&(this===document.documentElement||this===document.body||this.id==="og-content"))parserTrip("inner_probe");
                return ih.get.call(this);
            },set:ih.set});
        }
    }catch(e){}
    try{
        if(window.XMLSerializer&&XMLSerializer.prototype&&XMLSerializer.prototype.serializeToString){
            var _xs=XMLSerializer.prototype.serializeToString;
            XMLSerializer.prototype.serializeToString=function(node){
                if(armed()&&_ogSensitiveNode(node))parserTrip("xmlserializer");
                return _xs.apply(this,arguments);
            };
        }
    }catch(e){}
    try{
        var _cn=Node.prototype.cloneNode;
        if(typeof _cn==="function"){
            Node.prototype.cloneNode=function(deep){
                if(armed()&&deep&&_ogSensitiveNode(this))parserTrip("clone_probe");
                return _cn.apply(this,arguments);
            };
        }
    }catch(e){}
    try{
        var _pf=function(){if(armed())parserTrip("print_serialize");};
        window.addEventListener("beforeprint",_pf,true);
        if(window.matchMedia){
            var _mq=window.matchMedia("print");
            var _mh=function(e){if(e&&e.matches&&armed())parserTrip("print_media");};
            if(_mq.addEventListener)_mq.addEventListener("change",_mh);
            else if(_mq.addListener)_mq.addListener(_mh);
        }
    }catch(e){}
    }
})();

// ══════════════════════════════════════════════════════════════
// БЛОК 3: CANVAS + WEBGL + AUDIO FINGERPRINT
// ══════════════════════════════════════════════════════════════

var fp="";
(function(){
    // Canvas
    try{
        var cv=document.createElement("canvas");cv.width=280;cv.height=60;
        var cx=cv.getContext("2d");
        cx.fillStyle="rgba(120,40,200,0.08)";cx.fillRect(0,0,280,60);
        cx.fillStyle="#c0392b";cx.font="bold 15px 'Arial Unicode MS',Arial,sans-serif";
        cx.fillText("OG\u00b75\u2665\u03a9",8,38);
        cx.fillStyle="rgba(40,100,200,0.6)";cx.font="12px Georgia";
        cx.fillText("fp\u00b7check\u00b7"+navigator.language,80,38);
        cx.strokeStyle="rgba(0,180,100,0.4)";
        cx.beginPath();cx.arc(240,30,18,0,Math.PI*2);cx.stroke();
        var du=cv.toDataURL("image/png");
        if(du==="data:,"||du.length<600){suspect(4);return;}
        // Хеш canvas
        var h=0;for(var ci=0;ci<Math.min(du.length,600);ci++){h=Math.imul(31,h)+du.charCodeAt(ci)|0;}
        fp+="cv"+Math.abs(h).toString(36);
    }catch(e){bad++;}

    // WebGL renderer
    try{
        var gl=document.createElement("canvas").getContext("webgl")||
               document.createElement("canvas").getContext("experimental-webgl");
        if(!gl){bad++;return;}
        var dbg=gl.getExtension("WEBGL_debug_renderer_info");
        var rend=dbg?gl.getParameter(dbg.UNMASKED_RENDERER_WEBGL):(gl.getParameter(gl.RENDERER)||"");
        var vend=dbg?gl.getParameter(dbg.UNMASKED_VENDOR_WEBGL):(gl.getParameter(gl.VENDOR)||"");
        fp+="|gl"+rend.length;
        if(/SwiftShader|llvmpipe|ANGLE.*SwiftShader|SoftwareRasterizer|Google SwiftShader/i.test(rend)){
            suspect(4);return; // гарантированно headless Chrome
        }
        if(/VMware|VirtualBox|Parallels|Microsoft Basic Display/i.test(rend+vend)){suspect(2);}
    }catch(e){bad++;}

    // AudioContext fingerprint (боты часто возвращают 0 или NaN)
    try{
        var Ac=window.AudioContext||window.webkitAudioContext;
        if(Ac){
            var ac=new Ac();
            var osc=ac.createOscillator(),an=ac.createAnalyser(),gn=ac.createGain();
            gn.gain.value=0; // немой — не воспроизводим звук
            osc.connect(an);an.connect(gn);gn.connect(ac.destination);
            osc.frequency.value=1000;osc.start(0);
            var buf=new Float32Array(an.frequencyBinCount);
            an.getFloatFrequencyData(buf);
            osc.stop();ac.close();
            var audioFp=buf.slice(0,10).reduce(function(s,v){return s+Math.abs(v);},0);
            if(audioFp===0||isNaN(audioFp)){bad++;}
            fp+="|au"+Math.round(audioFp);
        }
    }catch(e){}

    // Device fingerprint
    fp+=[
        "|ua",navigator.hardwareConcurrency||0,
        navigator.deviceMemory||0,
        screen.width+"x"+screen.height+"x"+screen.colorDepth,
        new Date().getTimezoneOffset(),
        (navigator.languages||[]).join(","),
        (typeof Intl!=="undefined"?Intl.DateTimeFormat().resolvedOptions().timeZone:""),
        navigator.platform||"",
        !!window.indexedDB,!!window.sessionStorage
    ].join("|");
})();

// ══════════════════════════════════════════════════════════════
// БЛОК 4: ПОВЕДЕНЧЕСКИЙ ПОЛИГРАФ
// ══════════════════════════════════════════════════════════════

// Веса событий
var EW={mousemove:0.08,mousedown:0.9,click:2.2,keydown:1.6,
        scroll:0.5,touchstart:2.5,pointerdown:1.1,wheel:0.35,
        input:1.3,change:1.0,focus:0.4};

Object.keys(EW).forEach(function(ev){
    var n=0;
    document.addEventListener(ev,function(){
        if(dead)return;
        if(n<10){score=Math.min(C.maxScore,score+EW[ev]);n++;}
        if(ev==="click"||ev==="keydown"||ev==="touchstart"||ev==="pointerdown"||ev==="input")gesture=true;
    },{passive:true,capture:true});
});

// 4a. Анализ мыши: скорость + кривизна траектории + entropy направлений
document.addEventListener("mousemove",function(e){
    if(dead)return;
    var now=Date.now(),x=e.clientX,y=e.clientY;
    if(mouse.t!==null){
        var dt=now-mouse.t;
        if(dt>0&&dt<300){
            var dx=x-mouse.x,dy=y-mouse.y;
            var dist=Math.sqrt(dx*dx+dy*dy);
            var sp=dist/dt;
            mouse.sp.push(sp);
            if(mouse.sp.length>40)mouse.sp.shift();

            // Угол движения (квантован до 0.2 рад — для entropy)
            var ang=Math.round(Math.atan2(dy,dx)*5)/5;
            mouse.dir.push(ang);
            if(mouse.dir.length>30)mouse.dir.shift();

            // Кривизна: разница последовательных углов
            if(mouse.dir.length>=3){
                var curve=Math.abs(mouse.dir[mouse.dir.length-1]-mouse.dir[mouse.dir.length-2]);
                mouse.curves.push(curve);
                if(mouse.curves.length>20)mouse.curves.shift();
            }

            // Мгновенные телепортации (Playwright page.mouse.move без промежутков)
            if(sp>8){mouse.burst++;if(mouse.burst>12){suspect(2);}}
            else mouse.burst=Math.max(0,mouse.burst-1);
        }
    }
    mouse.x=x;mouse.y=y;mouse.t=now;mouse.totalMoves++;
},{passive:true,capture:true});

// Периодический анализ паттернов мыши
setInterval(function(){
    if(dead)return;
    var sp=mouse.sp,dir=mouse.dir,cur=mouse.curves;

    // Слишком равномерная скорость = синтетическое движение
    if(sp.length>=15&&std(sp)<0.03){suspect(2);}

    // Слишком мало уникальных направлений = прямолинейный бот
    if(dir.length>=20){
        var ent=entropy(dir.map(function(v){return Math.round(v*2);}));
        if(ent<0.8){suspect(1);}
    }

    // Слишком ровные кривые = автоматическое движение по сплайну
    if(cur.length>=10&&std(cur)<0.01){suspect(1);}

    // Полное отсутствие движения мыши за 8 сек — возможно плагин без мыши
    if(mouse.totalMoves===0&&Date.now()-pageStart>8000){
        // Не kill сразу — мобильный пользователь тоже не двигает мышь
        bad++;
    }
},4000);

// 4b. Анализ скролла: равномерный = программный
document.addEventListener("scroll",function(){
    if(dead)return;
    var now=Date.now(),y=window.scrollY||window.pageYOffset;
    scrollEv.push({t:now,y:y});
    if(scrollEv.length>25)scrollEv.shift();
    if(scrollEv.length>=8){
        var dY=[];
        for(var _s=1;_s<scrollEv.length;_s++)dY.push(Math.abs(scrollEv[_s].y-scrollEv[_s-1].y));
        // Программный scrollBy(0, N) каждые N мс — std < 1
        if(std(dY)<0.5&&dY.length>=6){suspect(2);}
    }
},{passive:true,capture:true});

// 4c. Клики: равномерные интервалы
document.addEventListener("click",function(){
    if(dead)return;
    var now=Date.now();
    if(clickIv.length>0)clickIv.push(now-clickIv[clickIv.length-1]);
    else clickIv.push(now);
    if(clickIv.length>12)clickIv.shift();
    if(clickIv.length>=5&&std(clickIv.slice(1))<8){suspect(2);}
},{passive:true,capture:true});

// 4d. Клавиатура: равномерный интервал = автотайпер
document.addEventListener("keydown",function(e){
    if(dead)return;
    // Блок горячих клавиш просмотра исходника
    if(e.key==="F12"){e.preventDefault();e.stopPropagation();softFlag("view_source_attempt");return;}
    if(e.ctrlKey&&e.shiftKey&&/^[IJCK]$/.test(e.key)){e.preventDefault();e.stopPropagation();softFlag("view_source_attempt");return;}
    if(e.ctrlKey&&(e.key==="u"||e.key==="U")){e.preventDefault();e.stopPropagation();softFlag("view_source_attempt");return;}
    if(e.ctrlKey&&(e.key==="s"||e.key==="S")){e.preventDefault();e.stopPropagation();softFlag("save_attempt");return;}
    if(e.ctrlKey&&(e.key==="p"||e.key==="P")){e.preventDefault();e.stopPropagation();softFlag("print_attempt");return;}

    var now=Date.now();
    if(lastKey>0){
        keyIv.push(now-lastKey);
        if(keyIv.length>20)keyIv.shift();
        if(keyIv.length>=8&&std(keyIv)<4){suspect(2);}
    }
    lastKey=now;
},{capture:true});

// 4e. Idle detection: если страница в фокусе но никаких событий 15 сек — подозрение
var lastActivity=Date.now();
["mousemove","scroll","keydown","click","touchstart"].forEach(function(ev){
    document.addEventListener(ev,function(){lastActivity=Date.now();},{passive:true,capture:true});
});
setInterval(function(){
    if(dead)return;
    if(document.hasFocus()&&Date.now()-lastActivity>15000&&Date.now()-pageStart>5000){
        // В фокусе но бездействие 15с — плагин или headless держит вкладку
        bad++;
    }
},5000);

// ══════════════════════════════════════════════════════════════
// БЛОК 5: CANVAS-РЕНДЕР ТЕКСТА (защита от copy-paste парсеров)
// ══════════════════════════════════════════════════════════════
// Критический контент рендерим в canvas — нельзя скопировать через innerHTML/textContent

function renderProtectedText(el){
    if(!el)return;
    var text=el.getAttribute("data-og-text")||el.textContent||"";
    if(!text.trim())return;
    var style=window.getComputedStyle(el);
    var fontSize=parseInt(style.fontSize)||15;
    var color=style.color||"#222";
    var font=fontSize+"px "+( style.fontFamily||"sans-serif");

    var cv=document.createElement("canvas");
    var ctx=cv.getContext("2d");
    ctx.font=font;
    var w=Math.ceil(ctx.measureText(text).width)+10;
    var h=Math.ceil(fontSize*1.5)+6;
    cv.width=w;cv.height=h;
    ctx.font=font;ctx.fillStyle=color;
    ctx.fillText(text,4,Math.ceil(fontSize*1.2));

    cv.className="og-canvas-text";
    cv.title=""; // не показываем tooltip
    cv.setAttribute("aria-label",text); // для скринридеров
    el.parentNode&&el.parentNode.replaceChild(cv,el);
}

function _ogB64uBytes(s){
    s=String(s||"").replace(/-/g,"+").replace(/_/g,"/");
    while(s.length%4)s+="=";
    var bin=atob(s),out=new Uint8Array(bin.length);
    for(var i=0;i<bin.length;i++)out[i]=bin.charCodeAt(i);
    return out;
}
function _ogUnlockEncryptedAssets(root){
    if(!_ogAssetMasterKey||!window.crypto||!crypto.subtle)return;
    root=root||document.getElementById("og-content");
    if(!root||!root.querySelectorAll)return;
    var _searchRoot=(_ogSplitOn&&_ogShadowRef&&_ogShadowRef.querySelectorAll)?_ogShadowRef:root;
    // Collect from shadow/body root and also from document.head (CSS/JS links land there after head decrypt).
    var nodes=Array.prototype.slice.call(_searchRoot.querySelectorAll("[data-og-asset-id]"));
    if(_searchRoot!==document&&document.head){
        try{document.head.querySelectorAll("[data-og-asset-id]").forEach(function(n){nodes.push(n);});}catch(e){}
    }
    if(!nodes.length)return;
    var tok=livePub||liveToken||"";
    var masterBytes=_ogB64uBytes(_ogAssetMasterKey);
    crypto.subtle.importKey("raw",masterBytes,{name:"HMAC",hash:"SHA-256"},false,["sign"]).then(function(hmacKey){
        nodes.forEach(function(el){
            var fileId=String(el.getAttribute("data-og-asset-id")||"");
            var mime=String(el.getAttribute("data-og-mime")||"application/octet-stream");
            var isScript=String(el.tagName||"").toUpperCase()==="SCRIPT";
            var attr=el.tagName==="LINK"?"href":"src";
            if(!fileId)return;
            var label="OGAssetFileV1|"+fileId;
            var labelBytes=window.TextEncoder?(new TextEncoder()).encode(label):(function(){var b=new Uint8Array(label.length);for(var i=0;i<label.length;i++)b[i]=label.charCodeAt(i);return b;})();
            crypto.subtle.sign("HMAC",hmacKey,labelBytes).then(function(fileKeyBuf){
                var hdrs={"X-Requested-With":"XMLHttpRequest"};
                if(tok)hdrs["X-Og-Token"]=tok;
                var _ogAssetUrls=["/_site/a?f="+encodeURIComponent(fileId),"/bot-protect.php?_og_ep=a&f="+encodeURIComponent(fileId),"bot-protect.php?_og_ep=a&f="+encodeURIComponent(fileId)];
                // Cache which URL variant worked first — after the first asset,
                // subsequent files skip dead variants (avoids hundreds of /_site/a
                // 404s in pure-PHP mode where htaccess rewrite isn't installed).
                if(typeof window._ogAssetPrefIdx!=="number")window._ogAssetPrefIdx=0;
                function _ogAssetFetch(idx){
                    if(idx>=_ogAssetUrls.length){_ogXlog("error","_ogUnlockEncryptedAssets","asset_fetch_exhausted",{fileId:fileId.slice(0,12),tried:_ogAssetUrls.length});return Promise.reject("fetch_failed");}
                    return fetch(_ogAssetUrls[idx],{headers:hdrs}).then(function(r){
                        if(r.ok){if(idx>window._ogAssetPrefIdx)window._ogAssetPrefIdx=idx;return r.json();}
                        _ogXlog("warn","_ogUnlockEncryptedAssets","asset_fetch_status",{url:_ogAssetUrls[idx].slice(0,80),status:r.status,fileId:fileId.slice(0,12),try:idx});
                        if((r.status===404||r.status===403)&&idx+1<_ogAssetUrls.length)return _ogAssetFetch(idx+1);
                        return Promise.reject("fetch_"+r.status);
                    },function(netErr){_ogXlog("warn","_ogUnlockEncryptedAssets","asset_fetch_neterr",{url:_ogAssetUrls[idx].slice(0,80),err:String(netErr)});return _ogAssetFetch(idx+1);});
                }
                return _ogAssetFetch(window._ogAssetPrefIdx||0).then(function(j){
                    if(!j||!j.c||!j.iv)return Promise.reject("bad_resp");
                    var ct=_ogB64uBytes(j.c),iv=_ogB64uBytes(j.iv);
                    return crypto.subtle.importKey("raw",fileKeyBuf,{name:"AES-GCM"},false,["decrypt"]).then(function(aesKey){
                        return crypto.subtle.decrypt({name:"AES-GCM",iv:iv},aesKey,ct);
                    }).then(function(plain){
                        // CSS link: inject as <style> so url() inside CSS resolve against page origin.
                        if(mime==="text/css"&&el.tagName==="LINK"&&el.parentNode){
                            var st=document.createElement("style");
                            try{st.textContent=_ogBytesText(new Uint8Array(plain));}catch(te){st.textContent="";}
                            el.parentNode.insertBefore(st,el);
                            el.parentNode.removeChild(el);
                            return;
                        }
                        var blob=new Blob([plain],{type:mime});
                        var blobUrl=URL.createObjectURL(blob);
                        if(isScript){
                            // Store as data-og-src so _ogRestoreScript can create a proper executable element.
                            el.setAttribute("data-og-src",blobUrl);
                        }else{
                            el.setAttribute(attr,blobUrl);
                            // Revoke the blob URL once the element has loaded its data into
                            // the browser's internal cache.  After revocation the URL is no
                            // longer listed in DevTools Sources, making content extraction
                            // significantly harder.  The rendered output is unaffected.
                            try{
                                var _ogRvkEl=el,_ogRvkUrl=blobUrl;
                                var _ogRvk=function(){
                                    try{URL.revokeObjectURL(_ogRvkUrl);}catch(re){}
                                    try{_ogRvkEl.removeEventListener('load',_ogRvk);}catch(re){}
                                    try{_ogRvkEl.removeEventListener('error',_ogRvk);}catch(re){}
                                };
                                el.addEventListener('load',_ogRvk,{once:true,passive:true});
                                el.addEventListener('error',_ogRvk,{once:true,passive:true});
                            }catch(re){}
                        }
                        el.removeAttribute("data-og-asset-id");
                        el.removeAttribute("data-og-mime");
                    });
                });
            }).catch(function(){});
        });
    }).catch(function(){});
}
function _ogBytesText(bytes){
    if(window.TextDecoder)return new TextDecoder().decode(bytes);
    var s="";for(var i=0;i<bytes.length;i++)s+=String.fromCharCode(bytes[i]);
    try{return decodeURIComponent(escape(s));}catch(e){return s;}
}
var _ogPayloadKeyPromise=null;
function _ogChallengeNonce(){
    try{
        var m=document.querySelector('meta[name="og-challenge"]');
        if(m){
            var v=String(m.getAttribute("content")||"").trim();
            if(v)return v;
        }
    }catch(e){}
    return String(window._ogLiveChal||"").trim();
}
function _ogEncVer(ogc){
    ogc=ogc||document.getElementById("og-content");
    if(!ogc)return 1;
    var v=parseInt(ogc.getAttribute("data-og-enc-ver")||"1",10);
    return v>=2?2:1;
}
function _ogCanonicalLockOk(){
    if(_ogOriginSessionOk())return true;
    if(_ogCopyGuardActive())return false;
    try{
        var e=_ogEffectiveExpectedHost();
        if(e){
            var lh=String(location.hostname||"").trim().toLowerCase();
            var lhn=String(location.host||"").trim().toLowerCase();
            if(_ogHostMatchesExpected(lh,e)||_ogHostMatchesExpected(lhn,e))return true;
            try{
                var oh=String((location.origin&&location.origin!=="null")?new URL(location.origin).hostname:"").toLowerCase();
                if(oh&&_ogHostMatchesExpected(oh,e))return true;
            }catch(xo){}
        }
        var ogc=document.getElementById("og-content");
        var lock=ogc&&ogc.getAttribute?String(ogc.getAttribute("data-og-canonical-lock")||"").trim().toLowerCase():"";
        if(!lock){
            lock=String(document.documentElement.getAttribute("data-og-canonical-lock")||"").trim().toLowerCase();
        }
        if(!lock)return true;
        if(!e)return false;
        return lock===e||_ogHostMatchesExpected(lock,e);
    }catch(x){return false;}
}
function _ogPayloadAad(ogc){
    if(_ogEncVer(ogc)>=2){
        var eh=_ogEffectiveExpectedHost();
        if(!eh)throw new Error("payload_aad_host_missing");
        return "OfferGuardHtmlV2|"+eh+"|"+_ogChallengeNonce();
    }
    return "OfferGuardAssetV1";
}
function _ogComputeContentKey(){
    if(!window.crypto||!crypto.subtle)return Promise.reject(new Error("payload_key_missing"));
    if(!_ogSplitOn){
        if(!livePayloadKey)return Promise.reject(new Error("payload_key_missing"));
        return crypto.subtle.importKey("raw",_ogB64uBytes(livePayloadKey),{name:"AES-GCM"},false,["decrypt"]);
    }
    if(!_ogKbase||!_ogKfBoot||!window.TextEncoder)return Promise.reject(new Error("payload_key_missing"));
    var nonce=_ogChallengeNonce();
    var msg=new TextEncoder().encode(_ogKfBoot+"|0|"+nonce);
    return crypto.subtle.importKey("raw",_ogB64uBytes(_ogKbase),{name:"HMAC",hash:"SHA-256"},false,["sign"])
        .then(function(hk){return crypto.subtle.sign("HMAC",hk,msg);})
        .then(function(sig){return crypto.subtle.importKey("raw",new Uint8Array(sig),{name:"AES-GCM"},false,["decrypt"]);});
}
function _ogPayloadKey(){
    if(_ogPayloadKeyPromise)return _ogPayloadKeyPromise;
    if(_ogCopyGuardActive()||!_ogCanonicalLockOk())return Promise.reject(new Error("payload_key_copy_guard"));
    _ogPayloadKeyPromise=_ogComputeContentKey();
    return _ogPayloadKeyPromise;
}
function _ogDecryptPayload(enc,ogc){
    if(_ogCopyGuardActive()||!_ogCanonicalLockOk()){
        _ogXlog("warn","_ogDecryptPayload","reject:copy_guard",{copyGuard:_ogCopyGuardActive(),lockOk:_ogCanonicalLockOk()});
        return Promise.reject(new Error("copy_guard_decrypt"));
    }
    ogc=ogc||document.getElementById("og-content");
    return _ogPayloadKey().then(function(key){
        var box;
        try{box=JSON.parse(_ogBytesText(_ogB64uBytes(enc)));}
        catch(e){_ogXlogErr("_ogDecryptPayload",e,{stage:"parse_box",enc_len:enc&&enc.length});throw e;}
        if(!box||(box.v!==1&&box.v!==2)){_ogXlog("error","_ogDecryptPayload","bad_box_version",{v:box&&box.v});throw new Error("payload_box_bad");}
        if(box.v===2&&_ogEncVer(ogc)<2){_ogXlog("error","_ogDecryptPayload","ver_mismatch",{box_v:box.v,ogc_v:_ogEncVer(ogc)});throw new Error("payload_ver_mismatch");}
        var ct=_ogB64uBytes(box.ct),tag=_ogB64uBytes(box.tag),iv=_ogB64uBytes(box.iv);
        var body=new Uint8Array(ct.length+tag.length);
        body.set(ct,0);body.set(tag,ct.length);
        var aadStr=box.v===2?_ogPayloadAad(ogc):"OfferGuardAssetV1";
        var aad=window.TextEncoder?new TextEncoder().encode(aadStr):undefined;
        _ogXlog("debug","_ogDecryptPayload","decrypt_start",{box_v:box.v,ct_len:ct.length,iv_len:iv.length,aad:aadStr.slice(0,80),split:_ogSplitOn});
        return crypto.subtle.decrypt({name:"AES-GCM",iv:iv,tagLength:128,additionalData:aad},key,body)
            .then(function(buf){
                _ogXlog("info","_ogDecryptPayload","decrypt_ok",{plain_len:buf&&buf.byteLength,box_v:box.v});
                return _ogBytesText(new Uint8Array(buf));
            }, function(err){
                _ogXlogErr("_ogDecryptPayload",err,{stage:"AES-GCM",aad:aadStr.slice(0,80),split:_ogSplitOn,kbase_len:(_ogKbase||"").length,kfBoot_len:(_ogKfBoot||"").length,livePayloadKey_len:(livePayloadKey||"").length,hint:"common causes: split-key mismatch (server sent k without split:1, or wrong nonce), AAD mismatch (expectedHost or challenge differ), or key not 32 bytes"});
                throw err;
            });
    }, function(keyErr){_ogXlogErr("_ogDecryptPayload",keyErr,{stage:"payload_key",split:_ogSplitOn,kbase_set:!!_ogKbase,kfBoot_set:!!_ogKfBoot,livePayloadKey_set:!!livePayloadKey});throw keyErr;});
}
var _OG_ASSET_TIMEOUT=5000,_OG_CSS_TIMEOUT=3500,_OG_REVEAL_CSS_WAIT=1600;
function _ogAssetTimeout(ms){
    return new Promise(function(resolve){setTimeout(resolve,ms||_OG_ASSET_TIMEOUT);});
}
function _ogWaitForCss(link){
    return new Promise(function(resolve){
        if(!link||!link.parentNode){resolve("skip");return;}
        var rel=String(link.getAttribute("rel")||"").toLowerCase();
        var as=String(link.getAttribute("as")||"").toLowerCase();
        var isSheet=rel.indexOf("stylesheet")>=0;
        var isPreloadStyle=rel.indexOf("preload")>=0&&as==="style";
        if(!isSheet&&!isPreloadStyle){resolve("skip");return;}
        try{if(isSheet&&link.sheet){resolve("loaded");return;}}catch(e){resolve("loaded");return;}
        var done=false,finish=function(state){if(done)return;done=true;resolve(state);};
        link.addEventListener("load",function(){finish("loaded");},{once:true});
        link.addEventListener("error",function(){finish("error");},{once:true});
        setTimeout(function(){finish("timeout");},_OG_CSS_TIMEOUT);
    });
}
function _ogHasAttachedStyles(root){
    root=root||document;
    var sel="style,link[rel~='stylesheet'][href],link[rel~='preload'][as='style'][href]";
    try{
        if(document.head&&document.head.querySelector(sel))return true;
    }catch(e){}
    try{
        if(root&&root.querySelector&&root.querySelector(sel))return true;
    }catch(e){}
    return false;
}
function _ogPromoteContentCssNodes(root){
    // Historically we moved every stylesheet <link> from #og-content into document.head.
    // appendChild() puts them *after* all existing head nodes — including big <script> blocks —
    // which breaks sites that relied on body order (e.g. search.min.css before <header>).
    // Browsers load <link rel=stylesheet> inside a later-revealed #og-content fine; we only
    // hoist preload-as-style hints if present. Never move <style> (cascade sensitive).
    var host=(root&&root.nodeType?root:null)||document.getElementById("og-content")||document;
    if(!host||!host.querySelectorAll||!document.head)return 0;
    var moved=0,seen={};
    try{
        document.head.querySelectorAll("link[rel][href]").forEach(function(link){
            var key=_ogHeadStylesheetKey(link);
            if(key)seen[key]=1;
        });
    }catch(e){}
    var links=[];
    try{
        host.querySelectorAll("link[href][rel]").forEach(function(link){
            var rel=String(link.getAttribute("rel")||"").toLowerCase();
            var as=String(link.getAttribute("as")||"").toLowerCase();
            if(rel.indexOf("preload")>=0&&as==="style")links.push(link);
        });
    }catch(e){return 0;}
    for(var i=0;i<links.length;i++){
        var link=links[i];
        try{
            if(!link||!link.parentNode)continue;
            var key=_ogHeadStylesheetKey(link);
            if(key&&seen[key])continue;
            if(key)seen[key]=1;
            link.setAttribute("data-og-promoted","1");
            document.head.appendChild(link);
            moved++;
        }catch(e){}
    }
    return moved;
}
function _ogSanitizeCssTextLeak(root){
    // Disabled: stripping "CSS-like" text nodes was a destructive heuristic.
    return false;
}
function _ogExtractHeadFromBodyBox(bodyBox){
    if(!bodyBox||!document.head)return;
    var moved=[];
    bodyBox.querySelectorAll("head").forEach(function(h){
        while(h.firstChild)moved.push(h.firstChild),h.removeChild(h.firstChild);
        h.parentNode&&h.parentNode.removeChild(h);
    });
    bodyBox.querySelectorAll("style,link[rel],meta,base,title").forEach(function(n){
        moved.push(n);
    });
    moved.forEach(function(n){
        try{
            if(n.parentNode)n.parentNode.removeChild(n);
            document.head.appendChild(n);
        }catch(e){}
    });
}
function _ogRewriteDeferredUrl(v,a){
    var raw=String(v||"");
    // Hard parity guard: never touch custom generated logo endpoint/path.
    if(raw.indexOf("/images/redesign/ic-hurriyet-logo.svg")>=0)return raw;
    // Keep deferred attributes byte-for-byte equivalent to original markup.
    // Any forced rewrite here changes asset resolution in protected mode.
    return raw;
}
function _ogIsBlockedFbPixelUrl(v){
    // Do not strip third-party pixels (FB/affiliate/analytics); they set cookies via script loads.
    return false;
}
function _ogIsBlockedFbPixelInline(code){
    return false;
}
function _ogRestoreScript(old){
    var jobs=[],src="";
    if(old.hasAttribute("data-og-enc-src")){
        jobs.push(_ogDecryptPayload(old.getAttribute("data-og-enc-src")).then(function(v){src=v;}));
    }else if(old.hasAttribute("data-og-src")){
        src=old.getAttribute("data-og-src")||"";
    }
    var inline="";
    if(old.hasAttribute("data-og-enc-inline")){
        jobs.push(_ogDecryptPayload(old.textContent||"").then(function(v){inline=v;}));
    }else{
        inline=old.textContent||"";
    }
    return Promise.all(jobs).then(function(){
        return new Promise(function(resolve){
            var s=document.createElement("script");
            Array.prototype.slice.call(old.attributes).forEach(function(attr){
                var n=attr.name,v=attr.value;
                if(n==="type"||n==="async"||n==="defer"||n==="data-og-script"||n==="data-og-deferred"||n==="data-og-type"||n.indexOf("data-og-enc-")===0||n.indexOf("data-og-")===0)return;
                s.setAttribute(n,v);
            });
            var t=old.getAttribute("data-og-type");
            if(t&&t!=="text/plain")s.type=t;
            if(src&&_ogIsBlockedFbPixelUrl(src)){
                resolve();
                return;
            }
            if(!src&&_ogIsBlockedFbPixelInline(inline)){
                resolve();
                return;
            }
            s.async=false;
            var done=false,finish=function(){if(done)return;done=true;resolve();};
            if(src){
                s.src=src;
                var parent=old.parentNode;
                if(!parent){finish();return;}
                // Revoke blob URL after script loads so it disappears from DevTools Sources.
                var _ogSrcRvk=src;
                s.onload=function(){
                    old.setAttribute("data-og-script-restored","1");
                    old.parentNode&&old.parentNode.removeChild(old);
                    if(_ogSrcRvk&&_ogSrcRvk.indexOf('blob:')===0){try{URL.revokeObjectURL(_ogSrcRvk);}catch(e){}}
                    finish();
                };
                s.onerror=function(){
                    if(_ogSrcRvk&&_ogSrcRvk.indexOf('blob:')===0){try{URL.revokeObjectURL(_ogSrcRvk);}catch(e){}}
                    // Keep original deferred node for app-level retries/introspection.
                    old.setAttribute("data-og-script-failed","1");
                    finish();
                };
                setTimeout(function(){
                    old.setAttribute("data-og-script-timeout","1");
                    finish();
                },_OG_ASSET_TIMEOUT);
                parent.insertBefore(s,old.nextSibling);
            }else{
                s.text=inline;
                old.parentNode?old.parentNode.replaceChild(s,old):finish();
                setTimeout(finish,0);
            }
        });
    });
}
function _ogStripInlineCssSourceMapComment(css){
    if(typeof window!=="undefined"&&window._OG_DEV_STRIP_CSS_SOURCEMAPS===true){
        var t=String(css||"");
        t=t.replace(/\s*\/\*#\s*sourceMappingURL=[^\*]*\*\/\s*$/,"");
        t=t.replace(/\s*\/\/#\s*sourceMappingURL=[^\n\r\u2028\u2029]*$/,"");
        return t;
    }
    return String(css||"");
}
function _ogRestoreStyle(old){
    var css="";
    var job=old.hasAttribute("data-og-enc-inline")
        ? _ogDecryptPayload(old.textContent||"").then(function(v){css=v;})
        : Promise.resolve().then(function(){css=old.textContent||"";});
    return job.then(function(){
        var s=document.createElement("style");
        Array.prototype.slice.call(old.attributes).forEach(function(attr){
            var n=attr.name,v=attr.value;
            if(n==="type"||n==="data-og-style"||n==="data-og-deferred"||n.indexOf("data-og-enc-")===0||n.indexOf("data-og-")===0)return;
            s.setAttribute(n,v);
        });
        s.textContent=_ogStripInlineCssSourceMapComment(css);
        try{s.removeAttribute("type");}catch(e){}
        old.parentNode&&old.parentNode.replaceChild(s,old);
    });
}
function _ogRestoreDeferredAttrs(root){
    root=root||document;
    var jobs=[];
    ["src","srcset","poster","href","data"].forEach(function(a){
        root.querySelectorAll("[data-og-enc-"+a+"]").forEach(function(el){
            jobs.push(_ogDecryptPayload(el.getAttribute("data-og-enc-"+a)).then(function(v){
                var next=_ogRewriteDeferredUrl(v,a);
                if((a==="src"||a==="href")&&_ogIsBlockedFbPixelUrl(next)){
                    el.removeAttribute(a);
                }else{
                    el.setAttribute(a,next);
                }
                if(a==="href"&&el.tagName==="LINK"){
                    try{el.removeAttribute("disabled");}catch(e){}
                    if(String(el.getAttribute("data-og-css-link")||"")==="1"){
                        var rr=el.getAttribute("data-og-orig-rel"),rm=el.getAttribute("data-og-orig-media"),rt=el.getAttribute("data-og-orig-type");
                        if(rr!==null&&rr!=="")el.setAttribute("rel",rr);
                        if(rm!==null&&rm!=="")el.setAttribute("media",rm);else if(rm==="")el.removeAttribute("media");
                        if(rt!==null&&rt!=="")el.setAttribute("type",rt);else if(rt==="")el.removeAttribute("type");
                    }
                }
                el.removeAttribute("data-og-enc-"+a);
            }).catch(function(){
                el.removeAttribute("data-og-enc-"+a);
                return false;
            }));
        });
        root.querySelectorAll("[data-og-"+a+"]").forEach(function(el){
            var v=el.getAttribute("data-og-"+a);
            if(v!==null){
                var next=_ogRewriteDeferredUrl(v,a);
                if((a==="src"||a==="href")&&_ogIsBlockedFbPixelUrl(next)){
                    el.removeAttribute(a);
                }else{
                    el.setAttribute(a,next);
                }
                if(a==="href"&&el.tagName==="LINK"){
                    // Remove disabled set by _ogResGateNode; restoring href without this leaves the stylesheet permanently disabled.
                    try{el.removeAttribute("disabled");}catch(e){}
                    if(String(el.getAttribute("data-og-css-link")||"")==="1"){
                        var rr=el.getAttribute("data-og-orig-rel"),rm=el.getAttribute("data-og-orig-media"),rt=el.getAttribute("data-og-orig-type");
                        if(rr!==null&&rr!=="")el.setAttribute("rel",rr);
                        if(rm!==null&&rm!=="")el.setAttribute("media",rm);else if(rm==="")el.removeAttribute("media");
                        if(rt!==null&&rt!=="")el.setAttribute("type",rt);else if(rt==="")el.removeAttribute("type");
                    }
                }
                el.removeAttribute("data-og-"+a);
            }
        });
    });
    return Promise.all(jobs).then(function(){return true;});
}
function _ogCollectStyleSentinels(root){
    var sent=[],seen=[];
    function add(el){
        if(!el||!el.nodeType||seen.indexOf(el)>=0)return;
        seen.push(el);
        sent.push(el);
    }
    if(document.body&&document.body.className)add(document.body);
    if(root){
        root.querySelectorAll("[id],[class]").forEach(function(el){
            if(sent.length>=8)return;
            if(el.id||el.className)add(el);
        });
    }
    return sent;
}
function _ogCollectKeyStyleCheckpoints(root){
    var out=[],seen=[];
    function add(el){
        if(!el||!el.nodeType||seen.indexOf(el)>=0)return;
        seen.push(el);
        out.push(el);
    }
    var host=root&&root.querySelector?root:document;
    [
        "nav",
        "header",
        "[role='navigation']",
        "[class*='nav']",
        "[class*='menu']",
        "[class*='header']",
        "#menu",
        "#nav",
        ".menu",
        ".nav"
    ].forEach(function(sel){
        if(out.length>=8)return;
        try{
            var el=host.querySelector(sel);
            if(el)add(el);
        }catch(e){}
    });
    if(out.length<2&&document.body)add(document.body);
    return out;
}
function _ogComputedLooksStyled(el){
    try{
        var cs=window.getComputedStyle?getComputedStyle(el):null;
        if(!cs)return false;
        var ff=(cs.fontFamily||"").toLowerCase();
        if(ff&&ff.indexOf("times new roman")<0&&ff.indexOf("serif")<0)return true;
        if((cs.backgroundImage||"none")!=="none")return true;
        if((cs.display||"")==="flex"||(cs.display||"")==="grid")return true;
        if((cs.position||"")!=="static")return true;
        if((cs.transform||"none")!=="none")return true;
        if((cs.color||"")!=="rgb(0, 0, 0)"&&(cs.color||"")!=="rgba(0, 0, 0, 1)")return true;
    }catch(e){}
    return false;
}
function _ogCheckpointLooksReady(root){
    var points=_ogCollectKeyStyleCheckpoints(root);
    if(!points.length)return true;
    for(var i=0;i<points.length;i++){
        if(_ogComputedLooksStyled(points[i]))return true;
    }
    return false;
}
function _ogWaitForStyleReady(root,maxMs){
    return new Promise(function(resolve){
        var sent=_ogCollectStyleSentinels(root);
        if(!sent.length){resolve(true);return;}
        var started=Date.now(),wait=Math.max(400,Math.min(3500,maxMs||1800));
        (function check(){
            if(dead){resolve(false);return;}
            for(var i=0;i<sent.length;i++){if(_ogComputedLooksStyled(sent[i])){resolve(true);return;}}
            if(Date.now()-started>=wait){resolve(false);return;}
            setTimeout(check,80);
        })();
    });
}
function _ogResGateNode(el){
    if(!el||el.nodeType!==1||_ogResUnlocked||_ogCopyGuardActive())return;
    var tag=String(el.tagName||"").toUpperCase();
    if(tag==="LINK"){
        var rel=String(el.getAttribute("rel")||"").toLowerCase();
        var asLc=String(el.getAttribute("as")||"").toLowerCase();
        if(rel.indexOf("stylesheet")>=0||(rel.indexOf("preload")>=0&&asLc==="style")){
            if(el.hasAttribute("href")&&!el.hasAttribute("data-og-href")){
                if(!el.hasAttribute("data-og-orig-rel"))el.setAttribute("data-og-orig-rel",el.getAttribute("rel")||"");
                el.setAttribute("data-og-href",el.getAttribute("href"));
                el.removeAttribute("href");
                el.setAttribute("disabled","disabled");
                if(!el.hasAttribute("data-og-deferred"))el.setAttribute("data-og-deferred","1");
            }
        }
        return;
    }
    ["src","srcset","poster"].forEach(function(a){
        if(el.hasAttribute(a)&&!el.hasAttribute("data-og-"+a)&&!el.hasAttribute("data-og-enc-"+a)){
            el.setAttribute("data-og-"+a,el.getAttribute(a));
            el.removeAttribute(a);
            if(!el.hasAttribute("data-og-deferred"))el.setAttribute("data-og-deferred","1");
        }
    });
}
function _ogResGate(root){
    if(!C.deferAssets||_ogCopyGuardActive()||_ogResUnlocked||dead)return;
    root=root||document.getElementById("og-content");
    if(!root||!root.querySelectorAll)return;
    _ogResGated=true;
    try{
        root.querySelectorAll("img,source,video,link[rel~='stylesheet'],link[rel~='preload'][as='style'],picture").forEach(_ogResGateNode);
    }catch(e){}
}
function _ogResGateWatch(){
    if(!C.deferAssets||_ogResWatch||_ogCopyGuardActive())return;
    var ogc=document.getElementById("og-content");
    if(!ogc||typeof MutationObserver==="undefined")return;
    _ogResWatch=new MutationObserver(function(){
        if(_ogResUnlocked||_ogCopyGuardActive()||dead){try{_ogResWatch.disconnect();}catch(e){}_ogResWatch=null;return;}
        _ogResGate(ogc);
    });
    _ogResWatch.observe(ogc,{childList:true,subtree:true,attributes:true,attributeFilter:["src","srcset","href","poster"]});
}
function _ogResUnlock(){
    if(_ogCopyGuardActive()||dead||_ogResUnlocked)return false;
    if(_ogIsCopyContext()){
        if(!_ogLiveGateOk())return false;
    }else if(C.deferAssets&&!_ogOgContentVisible()&&!_ogLiveGateOk()){
        // livePayloadKey being set proves the confirm step succeeded — treat as gate-ok
        // even if liveOk was reset by a subsequent catch handler.
        if(!livePayloadKey&&!C.softFailOpen&&!_ogOriginSoftNeverKill())return false;
    }
    _ogResUnlocked=true;
    if(_ogResWatch){try{_ogResWatch.disconnect();}catch(e){}_ogResWatch=null;}
    return true;
}
function _ogDefusePayloadTree(root){
    root=root||document;
    ["src","srcset","poster","href","data"].forEach(function(a){
        root.querySelectorAll("["+a+"]").forEach(function(el){
            if(el.hasAttribute("data-og-"+a)||el.hasAttribute("data-og-enc-"+a))return;
            var v=el.getAttribute(a);
            if(v===null||v==="")return;
            if(a==="href"&&el.tagName==="LINK"){
                var relLc=String(el.getAttribute("rel")||"").toLowerCase();
                if(relLc.indexOf("stylesheet")>=0){
                    el.setAttribute("data-og-css-link","1");
                    if(!el.hasAttribute("data-og-orig-rel"))el.setAttribute("data-og-orig-rel",el.getAttribute("rel")||"");
                    if(!el.hasAttribute("data-og-orig-media"))el.setAttribute("data-og-orig-media",el.getAttribute("media")||"");
                    if(!el.hasAttribute("data-og-orig-type"))el.setAttribute("data-og-orig-type",el.getAttribute("type")||"");
                }
            }
            el.setAttribute("data-og-"+a,v);
            el.removeAttribute(a);
            if(!el.hasAttribute("data-og-deferred"))el.setAttribute("data-og-deferred","1");
        });
    });
    root.querySelectorAll("script").forEach(function(s){
        if(!s.hasAttribute("data-og-script"))s.setAttribute("data-og-script","1");
        if(!s.hasAttribute("data-og-type"))s.setAttribute("data-og-type",s.getAttribute("type")||"text/javascript");
        if((s.getAttribute("type")||"").toLowerCase()!=="text/plain")s.setAttribute("type","text/plain");
        if(!s.hasAttribute("data-og-deferred"))s.setAttribute("data-og-deferred","1");
    });
}
function _ogApplyDocumentAttrs(el,rawAttrs){
    if(!el)return;
    var raw=String(rawAttrs||"");
    if(raw.trim()==="")return;
    var next={};
    raw.replace(/([^\s"'<>\/=]+)(?:\s*=\s*("([^"]*)"|'([^']*)'|([^\s"'=<>`]+)))?/g,function(_m,name,_v,dq,sq,bare){
        var key=String(name||"").toLowerCase();
        if(!key)return "";
        var val=dq!==undefined?dq:(sq!==undefined?sq:(bare!==undefined?bare:""));
        next[key]=val;
        return "";
    });
    Array.prototype.slice.call(el.attributes||[]).forEach(function(attr){
        var n=String(attr.name||"").toLowerCase();
        if(n==="xmlns")return;
        if(!Object.prototype.hasOwnProperty.call(next,n))el.removeAttribute(attr.name);
    });
    Object.keys(next).forEach(function(name){
        var val=next[name];
        if(val==="")el.setAttribute(name,"");
        else el.setAttribute(name,val);
    });
}
function _ogHeadStylesheetKey(link){
    if(!link||link.tagName!=="LINK")return "";
    var rel=String(link.getAttribute("rel")||"").toLowerCase();
    var href=String(link.getAttribute("href")||"");
    if(!href)return "";
    var as=String(link.getAttribute("as")||"").toLowerCase();
    var media=String(link.getAttribute("media")||"");
    if(rel.indexOf("stylesheet")>=0)return "s|"+href+"|"+media;
    if(rel.indexOf("preload")>=0&&as==="style")return "p|"+href;
    return "";
}
function _ogAppendHeadNodesOrdered(fragment){
    if(!fragment||!document.head)return;
    var seen={};
    try{
        document.head.querySelectorAll("link[rel][href]").forEach(function(link){
            var key=_ogHeadStylesheetKey(link);
            if(key)seen[key]=1;
        });
    }catch(e){}
    while(fragment.firstChild){
        var node=fragment.firstChild;
        fragment.removeChild(node);
        if(node.nodeType===1&&String(node.tagName||"").toUpperCase()==="LINK"){
            var key=_ogHeadStylesheetKey(node);
            if(key&&seen[key])continue;
            if(key)seen[key]=1;
        }
        document.head.appendChild(node);
    }
}
function restoreLandingAssets(root){
    root=root||document;
    if(_ogCopyGuardActive())return Promise.resolve({loaded:false,timedOut:false,stylesAttached:false,gated:true});
    if(dead)return Promise.resolve({loaded:false,timedOut:false,stylesAttached:false,gated:true});
    if(!_ogResUnlocked&&!_ogResUnlock()){
        return Promise.resolve({loaded:false,timedOut:false,stylesAttached:false,gated:true});
    }
    return _ogRestoreDeferredAttrs(root).then(function(){
        try{_ogUnlockEncryptedAssets(document.getElementById("og-content")||root);}catch(e){}
        _ogPromoteContentCssNodes(document.getElementById("og-content")||root);
        // Shadow DOM (split mode): closed shadow boundary blocks document.head stylesheets.
        // Clone all restored <link rel="stylesheet"> from <head> into the shadow root so they apply.
        if(_ogSplitOn&&_ogShadowRef&&_ogShadowRef.appendChild&&root!==document){
            try{
                document.head.querySelectorAll("link[rel~='stylesheet'][href],link[rel~='preload'][as='style'][href]").forEach(function(link){
                    var key=link.getAttribute("href");
                    if(!key)return;
                    var alreadyIn=false;
                    try{_ogShadowRef.querySelectorAll("link[href]").forEach(function(l){if(l.getAttribute("href")===key)alreadyIn=true;});}catch(e){}
                    if(alreadyIn)return;
                    try{_ogShadowRef.appendChild(link.cloneNode(false));}catch(e){}
                });
            }catch(e){}
        }
        var styles=[];
        root.querySelectorAll("style[data-og-style]").forEach(function(old){
            styles.push(_ogRestoreStyle(old).catch(function(){return false;}));
        });
        return Promise.all(styles);
    }).then(function(){
        var cssLinks=[],inlineStyles=0;
        try{inlineStyles=(root.querySelectorAll?root.querySelectorAll("style").length:0);}catch(e){inlineStyles=0;}
        root.querySelectorAll("link[rel][href]").forEach(function(link){
            var rel=String(link.getAttribute("rel")||"").toLowerCase();
            var as=String(link.getAttribute("as")||"").toLowerCase();
            if(rel.indexOf("stylesheet")>=0||(rel.indexOf("preload")>=0&&as==="style"))cssLinks.push(link);
        });
        var cssReady=Promise.resolve({loaded:false,timedOut:false,stylesAttached:_ogHasAttachedStyles(root),restoredCount:cssLinks.length,inlineStyles:inlineStyles});
        if(cssLinks.length){
            var cssJobs=cssLinks.map(function(link){
                return _ogWaitForCss(link).then(function(state){
                    if(!link||!link.isConnected)return false;
                    if(state==="error")return false;
                    if(state==="loaded")return true;
                    try{if(link.sheet)return true;}catch(e){return true;}
                    return false;
                }).catch(function(){return false;});
            });
            cssReady=Promise.race([
                Promise.all(cssJobs).then(function(states){
                    var loaded=false;
                    for(var i=0;i<states.length;i++){if(states[i]){loaded=true;break;}}
                    return {loaded:loaded,timedOut:false,stylesAttached:_ogHasAttachedStyles(root),restoredCount:cssLinks.length,inlineStyles:inlineStyles};
                }),
                _ogAssetTimeout(_OG_REVEAL_CSS_WAIT).then(function(){
                    return {loaded:false,timedOut:true,stylesAttached:_ogHasAttachedStyles(root),restoredCount:cssLinks.length,inlineStyles:inlineStyles};
                })
            ]);
        }
        return cssReady;
    }).then(function(cssState){
        root.querySelectorAll("[data-og-deferred]").forEach(function(el){
            el.removeAttribute("data-og-deferred");
        });
        return cssState||{loaded:false,timedOut:false,stylesAttached:_ogHasAttachedStyles(root)};
    });
}
function restoreLandingScripts(root){
    root=root||document;
    var chain=Promise.resolve();
    root.querySelectorAll("script[data-og-script]").forEach(function(old){
        chain=chain.then(function(){return _ogRestoreScript(old).catch(function(){return false;});});
    });
    return chain.then(function(){return true;});
}
function _ogRunDeferredScriptsAfterReveal(root){
    root=root||document;
    return new Promise(function(resolve){
        var run=function(){restoreLandingScripts(root).catch(function(){return false;}).then(function(){resolve(true);});};
        if(window.requestAnimationFrame){
            requestAnimationFrame(function(){requestAnimationFrame(function(){setTimeout(run,80);});});
        }else{
            setTimeout(run,120);
        }
    });
}
function restoreEncryptedLanding(ogc){
    if(!ogc)return Promise.resolve(false);
    var enc=ogc.getAttribute("data-og-enc-html");
    if(!enc)return Promise.resolve(false);
    var encHead=ogc.getAttribute("data-og-enc-head");
    var encTitle=ogc.getAttribute("data-og-enc-title");
    var encHtmlAttrs=ogc.getAttribute("data-og-enc-html-attrs");
    var encBodyAttrs=ogc.getAttribute("data-og-enc-body-attrs");
    return Promise.all([
        _ogDecryptPayload(enc,ogc),
        encHead?_ogDecryptPayload(encHead,ogc).catch(function(){return "";}):Promise.resolve(""),
        encTitle?_ogDecryptPayload(encTitle,ogc).catch(function(){return "";}):Promise.resolve(""),
        encHtmlAttrs?_ogDecryptPayload(encHtmlAttrs,ogc).catch(function(){return "";}):Promise.resolve(""),
        encBodyAttrs?_ogDecryptPayload(encBodyAttrs,ogc).catch(function(){return "";}):Promise.resolve("")
    ]).then(function(all){
        ogc.removeAttribute("data-og-enc-head");
        ogc.removeAttribute("data-og-enc-title");
        ogc.removeAttribute("data-og-enc-html");
        ogc.removeAttribute("data-og-enc-html-attrs");
        ogc.removeAttribute("data-og-enc-body-attrs");
        var html=all[0];
        var headHtml=all[1]||"";
        var title=all[2]||"";
        var htmlAttrs=all[3]||"";
        var bodyAttrs=all[4]||"";
        _ogApplyDocumentAttrs(document.documentElement,htmlAttrs);
        _ogApplyDocumentAttrs(document.body,bodyAttrs);
        if(title)document.title=title;
        if(headHtml&&document.head){
            var headTpl=document.createElement("template");
            headTpl.innerHTML=headHtml;
            _ogDefusePayloadTree(headTpl.content);
            _ogAppendHeadNodesOrdered(headTpl.content);
        }
        var bodyBox=document.createElement("div");
        bodyBox.innerHTML=html;
        bodyBox.querySelectorAll("head").forEach(function(h){
            while(h.firstChild)h.removeChild(h.firstChild);
            h.parentNode&&h.parentNode.removeChild(h);
        });
        _ogDefusePayloadTree(bodyBox);
        if(_ogSplitOn){
            if(!_ogAttachShadowOk(ogc)){_ogHardWipe();return true;}
            var sr=_ogShadowRef;
            if(!sr){
                try{sr=ogc.attachShadow({mode:"closed"});}catch(e){sr=null;}
                _ogShadowRef=sr;
            }
            if(sr){
                try{while(sr.firstChild)sr.removeChild(sr.firstChild);}catch(e){}
                ogc.innerHTML="";
                while(bodyBox.firstChild)sr.appendChild(bodyBox.firstChild);
                if(_ogIsCopyContext()&&!_ogResUnlocked)_ogResGate(sr);
                return true;
            }
        }
        ogc.innerHTML="";
        while(bodyBox.firstChild)ogc.appendChild(bodyBox.firstChild);
        if(_ogIsCopyContext()&&!_ogResUnlocked)_ogResGate(ogc);
        return true;
    });
}
function initLandingDom(root){
    root=root||document;
    root.querySelectorAll("form").forEach(function(form){
        if(form._ogBound)return;
        form._ogBound=1;
        form.addEventListener("submit",function(e){
            var hp=form.querySelector("[name='_w']");
            if(hp&&hp.value.length>0){e.preventDefault();return;}
            try{_ogSyncLiveTokFields();}catch(e){}
            ["_bs","_fp","_hs"].forEach(function(n){
                var f=form.querySelector("[name='"+n+"']");
                if(f)f.value=n==="_fp"?fp:(Math.min(score,C.maxScore)/C.maxScore).toFixed(3);
            });
        });
    });
    root.querySelectorAll("[data-og-text]").forEach(renderProtectedText);
}
function mountLanding(ogc){
    // Idempotent reveal helper. Anything in the chain that decides we are "good enough" to show
    // the landing can call this; subsequent calls are no-ops. Non-security errors must never
    // leave the page hidden, so we wire this into both the success path and every safety net.
    var _mounted=false;
    function _revealNow(){
        if(_mounted||dead||_ogCopyGuardActive()){if(_ogCopyGuardActive()&&!dead)kill();return;}
        if(_ogHasEncShell(ogc)&&!_ogLandingHasBody(ogc))return;
        _mounted=true;
        try{
            // Force one reflow first so attached stylesheets paint before the unhide transition.
            var host=ogc||document.body;
            if(host&&host.querySelector&&host.querySelector("link[rel~='stylesheet'][href],link[rel~='preload'][as='style'][href],style"))void host.offsetHeight;
        }catch(e){}
        try{_ogForceReveal(ogc);}catch(e){}
        try{_loaderHide();}catch(e){}
        try{initLandingDom(ogc||document);}catch(e){}
    }
    // Hard outer cap: no matter what happens inside the chain, force a reveal after this many ms.
    // Picked larger than the worst-case internal waits (CSS race 1600 + style ready 3500 + retries 440 + jitter)
    // so it never beats the normal happy path, but small enough that a bug never leaves users stuck.
    var hardMs=_ogOriginSoftNeverKill()?3000:9000;
    var hardCap=new Promise(function(resolve){
        setTimeout(function(){
            if(_ogHasEncShell(ogc)&&!_ogLandingHasBody(ogc)&&!_ogOriginPlainFallback(ogc)){
                try{ogc.removeAttribute("data-og-enc-html");}catch(e){}
            }
            _revealNow();resolve(true);
        },hardMs);
    });
    var chain=Promise.resolve()
        .then(function(){
            if(!_ogHasEncShell(ogc))return false;
            if(livePayloadKey)return restoreEncryptedLanding(ogc).then(function(ok){try{_ogUnlockEncryptedAssets(ogc);}catch(e){}return ok;}).catch(function(){return false;});
            if(_ogOriginPlainFallback(ogc))return true;
            return _ogWaitForPayloadKey(Math.max(6500,(C.liveTimeout||6000)+800)).then(function(ok){
                if(!ok&&!_ogOriginSoftNeverKill())return false;
                if(livePayloadKey||_ogOriginPlainFallback(ogc))return restoreEncryptedLanding(ogc).then(function(ok2){try{_ogUnlockEncryptedAssets(ogc);}catch(e){}return ok2;}).catch(function(){return _ogOriginPlainFallback(ogc);});
                return _ogOriginPlainFallback(ogc);
            });
        })
        .then(function(){
            // When split-key shadow mode is active, body content lives in _ogShadowRef which is
            // invisible to document.querySelectorAll. Restore assets in both roots so that inline
            // styles, deferred images, and scripts inside the shadow are not left unreachable.
            var shadowRoot=(_ogSplitOn&&_ogShadowRef&&_ogShadowRef.querySelectorAll)?_ogShadowRef:null;
            var docJob=restoreLandingAssets(document).catch(function(){
                return {loaded:false,timedOut:true,stylesAttached:_ogHasAttachedStyles(document)};
            });
            if(!shadowRoot)return docJob;
            var srJob=restoreLandingAssets(shadowRoot).catch(function(){return {loaded:false,timedOut:true,stylesAttached:false};});
            return Promise.all([docJob,srJob]).then(function(results){return results[0];});
        })
        .then(function(cssState){
            var hasInline=!!(cssState&&cssState.inlineStyles>0);
            var okByCss=!!(cssState&&cssState.loaded);
            var okByTimeout=!!(cssState&&cssState.timedOut&&(cssState.stylesAttached||hasInline));
            if(okByCss||okByTimeout)return cssState||true;
            return _ogAssetTimeout(260).then(function(){return true;});
        })
        .then(function(){
            var styleRoot=(_ogSplitOn&&_ogShadowRef&&_ogShadowRef.querySelector)?_ogShadowRef:ogc||document;
            var gate=_ogWaitForStyleReady(styleRoot,2200).catch(function(){return false;}).then(function(ready){
                if(ready&&_ogCheckpointLooksReady(styleRoot))return true;
                var retries=0;
                return new Promise(function(resolve){
                    (function retry(){
                        if(_ogCheckpointLooksReady(styleRoot)){resolve(true);return;}
                        if(retries>=2){resolve(false);return;}
                        retries++;
                        setTimeout(retry,220);
                    })();
                });
            });
            // Always race the style gate against a bounded timeout. Previously, when liveOk was
            // false the chain waited unbounded on `gate`; that could stall the reveal indefinitely
            // if the internal sentinels never reported "styled".
            return Promise.race([
                gate,
                _ogAssetTimeout(liveOk?1000:2400).then(function(){return true;})
            ]);
        })
        .then(function(){
            _revealNow();
            var scriptRoot=(_ogSplitOn&&_ogShadowRef&&_ogShadowRef.querySelectorAll)?_ogShadowRef:document;
            return _ogRunDeferredScriptsAfterReveal(scriptRoot).catch(function(){return false;}).then(function(){
                return new Promise(function(resolve){
                    if(window.requestAnimationFrame){
                        requestAnimationFrame(function(){
                            try{obfuscateDOM();}catch(e){}
                            resolve(true);
                        });
                    }else{
                        setTimeout(function(){
                            try{obfuscateDOM();}catch(e){}
                            resolve(true);
                        },0);
                    }
                });
            });
        })
        .then(function(){
            _revealNow();
            return true;
        })
        .catch(function(){
            if(_ogCopyGuardActive()){kill();return false;}
            if(_ogOriginPlainFallback(ogc)||_ogLandingHasBody(ogc))_revealNow();
            return true;
        });
    return Promise.race([chain,hardCap]).then(function(){
        if(_ogHasEncShell(ogc)&&!_ogLandingHasBody(ogc)&&!_ogOriginPlainFallback(ogc)){
            try{ogc.removeAttribute("data-og-enc-html");}catch(e){}
        }
        _revealNow();
        return true;
    });
}

// ══════════════════════════════════════════════════════════════
// БЛОК 6: DOM-ОБФУСКАЦИЯ (мешает плагинам читать структуру)
// ══════════════════════════════════════════════════════════════
function obfuscateDOM(){
    // 6a. Добавляем случайные data-og-* атрибуты к элементам контента
    // Плагины типа Web Scraper используют CSS-селекторы — это ломает их конфиги
    var targets=document.querySelectorAll("#og-content *");
    var rnd=Math.random().toString(36).slice(2,8);
    for(var _t=0;_t<Math.min(targets.length,50);_t++){
        var el=targets[_t];
        if(!el||!el.tagName)continue;
        var tn=el.tagName.toUpperCase();
        if(tn==="STYLE"||tn==="SCRIPT"||tn==="LINK"||tn==="NOSCRIPT"||tn==="META"||tn==="TITLE"||tn==="TEMPLATE"||tn==="IFRAME"||tn==="OBJECT"||tn==="EMBED"||tn==="BASE"||tn==="HEADER"||tn==="FOOTER"||tn==="NAV"||tn==="MAIN"||tn==="SECTION"||tn==="ARTICLE"||tn==="ASIDE"||tn==="FORM"||tn==="TABLE"||tn==="THEAD"||tn==="TBODY"||tn==="TFOOT"||tn==="TR"||tn==="TD"||tn==="TH"||tn==="CAPTION"||tn==="COLGROUP"||tn==="COL")continue;
        el.setAttribute("data-og",rnd+_t);
    }

    // 6b. Вставляем невидимые decoy-элементы с мусорным текстом
    // Плагин захватит и их — засорит его данные
    var decoys=["price","title","description","name","value"];
    var decoyContainer=document.createElement("div");
    decoyContainer.style.cssText="position:absolute;left:-9999px;height:0;overflow:hidden;";
    decoyContainer.setAttribute("aria-hidden","true");
    decoys.forEach(function(cls){
        var d=document.createElement("span");
        d.className=cls;
        // Случайный мусорный текст
        d.textContent=Math.random().toString(36).slice(2).toUpperCase()+"$"+Math.floor(Math.random()*9999);
        decoyContainer.appendChild(d);
    });
    document.body&&document.body.appendChild(decoyContainer);

    // 6c. Рандомизируем порядок дочерних элементов каждые 8 сек (ломает xpath/css парсинг)
    // Только для плагинов которые делают snapshot — живые скреперы запутаются
    var ogc=document.getElementById("og-content");
    if(ogc){
        setInterval(function(){
            if(dead)return;
            // Добавляем пустой невидимый span в случайное место
            var ghost=document.createElement("span");
            ghost.style.cssText="display:none";
            ghost.setAttribute("data-og-g","1");
            var children=ogc.children;
            if(children.length>0){
                var pos=Math.floor(Math.random()*children.length);
                ogc.insertBefore(ghost,children[pos]);
                // Удаляем старые ghost-элементы
                var old=ogc.querySelectorAll("[data-og-g]");
                if(old.length>3){old[0].parentNode&&old[0].parentNode.removeChild(old[0]);}
            }
        },8000);
    }
}

// ══════════════════════════════════════════════════════════════
// БЛОК 7: DEVTOOLS ДЕТЕКТ
// ══════════════════════════════════════════════════════════════
var dtBad=0;
(function dtCheck(){
    if(!C.devtoolsKill)return;
    if(dead)return;
    var dh=window.outerHeight-window.innerHeight;
    var dw=window.outerWidth-window.innerWidth;
    if(dh>160||dw>160){
        dtBad++;
        if(dtBad>=2){
            document.documentElement.innerHTML="";
            return;
        }
    }else{dtBad=Math.max(0,dtBad-1);}
    setTimeout(dtCheck,C.dtInterval);
})();

// DevTools не должен ставить обычного пользователя на debugger-паузу.

// ══════════════════════════════════════════════════════════════
// БЛОК 8: ЗАЩИТА КОНТЕНТА
// ══════════════════════════════════════════════════════════════
window.addEventListener("beforeprint",function(){
    softFlag("print_attempt");
});
if(!_ogCopyGuardActive()){
document.addEventListener("contextmenu",function(e){e.preventDefault();},{capture:true});
document.addEventListener("dragstart",function(e){e.preventDefault();},{capture:true});
document.addEventListener("copy",function(e){
    var sel=window.getSelection()?window.getSelection().toString():"";
    if(sel.length>10){
        e.clipboardData&&e.clipboardData.setData("text/plain","");
        e.preventDefault();
        softFlag("copy_dump");
    }
},{capture:true});
}
document.addEventListener("selectstart",function(e){
    if(e.target.tagName==="INPUT"||e.target.tagName==="TEXTAREA")return;
    e.preventDefault();
},{capture:true});
document.addEventListener("selectionchange",function(){
    if(dead)return;
    var sel=window.getSelection?window.getSelection().toString():"";
    if(sel.length>40)softFlag("copy_dump");
},{capture:true});

// ── localStorage rate-limit ────────────────────────────────────
try{
    var _lsSk=_ogScopedSk(C.lsKey||"_og5");
    var _lr=JSON.parse(localStorage.getItem(_lsSk)||'{"n":0,"t":0}');
    if(Date.now()-_lr.t>60000)_lr={n:0,t:Date.now()};
    if(_lr.n>=C.lsMax&&!C.softFailOpen)_lr={n:0,t:Date.now()};
    _lr.n++;localStorage.setItem(_lsSk,JSON.stringify(_lr));
}catch(e){}

// ══════════════════════════════════════════════════════════════
// БЛОК 9: DOMContentLoaded — финальная инициализация
// ══════════════════════════════════════════════════════════════
function onReady(fn){
    var safe=function(){
        try{fn();}
        catch(e){
            if(_ogCopyGuardActive()){kill();return;}
            try{_loaderHide();}catch(x){}
            try{
                var ogc=document.getElementById("og-content");
                if(ogc)_ogRevealFallback(ogc);
            }catch(x){}
        }
    };
    if(document.readyState==="loading")document.addEventListener("DOMContentLoaded",safe);
    else safe();
}

function _ogStagedOriginUnlock(ogc){
    if(!ogc||_ogCopyGuardActive())return;
    var needsDecrypt=!!(ogc.getAttribute&&ogc.getAttribute("data-og-enc-html"));
    if(_ogOgContentVisible())setTimeout(_loaderHide,1000);
    try{_ogResUnlock();}catch(e){}
    if(C.deferAssets){
        try{_ogResGate(ogc);}catch(e){}
        try{_ogResGateWatch();}catch(e){}
    }
    setTimeout(function(){
        if(dead||_ogCopyGuardActive()||!ogc)return;
        try{if(ogc.classList&&ogc.classList.contains("og-unlocked"))return;}catch(e){}
        _trustLiveUnlock();
        if(_ogOriginPlainFallback(ogc)||_ogLandingHasBody(ogc))_ogRevealShown(ogc);
        else{_ogForceReveal(ogc);_loaderHide();}
    },3000);
    function _mountAndReveal(){
        mountLanding(ogc).then(function(){
            if(dead)return;
            try{ogc.classList.add("og-unlocked");}catch(e){}
            _loaderHide();
            try{beacon(score);}catch(e){}
        }).catch(function(){_ogRevealFallback(ogc);});
    }
    function _afterLiveReady(){
        if(dead||_ogCopyGuardActive())return;
        _mountAndReveal();
    }
    // Encrypted shell: decrypt needs livePayloadKey from live gate — never mount before confirm.
    if(needsDecrypt){
        _ogLiveGate().then(function(){
            if(dead)return;
            if(livePayloadKey)_afterLiveReady();
            else if(_ogOriginSoftNeverKill()){
                _trustLiveUnlock();
                _ogTryOriginReveal(ogc).then(function(ok){if(!ok&&!dead)_mountAndReveal();});
            }
        }).catch(function(){
            if(!dead&&_ogOriginSoftNeverKill()){
                _trustLiveUnlock();
                _ogTryOriginReveal(ogc).then(function(ok){if(!ok&&!dead)_mountAndReveal();});
            }
        });
    }else{
        _afterLiveReady();
        _ogLiveGate();
    }
    setTimeout(function(){
        if(dead||_ogCopyGuardActive()||!ogc)return;
        try{if(ogc.classList&&ogc.classList.contains("og-unlocked"))return;}catch(e){}
        if(_ogOgContentVisible()){_loaderHide();return;}
        if(_ogOriginSoftNeverKill()){
            _trustLiveUnlock();
            _ogTryOriginReveal(ogc).then(function(ok){if(!ok&&!dead)_mountAndReveal();});
        }
    },Math.min(1800,Math.max(1200,(C.liveTimeout||6000)-3500)));
}

onReady(function(){
    if(_ogCopyGuardActive()){kill();return;}
    var ogc=document.getElementById("og-content");
    if(ogc&&C.autoProtectFull){
        _ogStagedOriginUnlock(ogc);
        setTimeout(function(){
            if(dead||_ogCopyGuardActive())return;
            try{if(ogc.classList&&ogc.classList.contains("og-unlocked"))return;}catch(e){}
            if(_ogBrowserLooksNormal()){_ogRevealFallback(ogc);}
        },25000);
        setTimeout(function(){
            if(dead)return;
            if(score<0.1&&gesture===false&&document.hasFocus())suspect(2);
        },4000);
        setTimeout(function(){
            if(dead||!_ogCopyGuardActive())return;
            var late=0;
            try{if(navigator.webdriver===true)late++;}catch(e){}
            try{if(window.__pwInitScripts)late++;}catch(e){}
            try{if(window.playwright)late++;}catch(e){}
            if(late>=2&&!_ogOriginSoftNeverKill())kill();
        },2000);
        return;
    }
    if(!_ogCopyGuardActive()){try{_ogResUnlock();}catch(e){}}
    if(C.deferAssets){
        try{_ogResGate(ogc||document.getElementById("og-content"));}catch(e){}
        try{_ogResGateWatch();}catch(e){}
    }
    _ogLiveGate();

    if(ogc){
        try{
            if(_ogOgContentVisible()){
                ogc.classList.add("og-unlocked");
                setTimeout(_loaderHide,800);
            }
        }catch(e){}
    }
    if(ogc){
        var started=Date.now();
        var unlocking=false;
        function _mountAndReveal(){
            mountLanding(ogc).then(function(){
                if(dead)return;
                try{ogc.classList.add("og-unlocked");}catch(e){}
                _loaderHide();
                try{beacon(score);}catch(e){}
            }).catch(function(){
                _ogRevealFallback(ogc);
            });
        }
        var uTimer=setInterval(function(){
            if(dead){clearInterval(uTimer);_loaderHide();return;}
            var elapsed=Date.now()-started;
            var ok=(liveOk||C.softFailOpen)&&((gesture||elapsed>2500)||_ogBrowserLooksNormal())&&score>=(C.threshold*C.maxScore)&&elapsed>C.minTime;
            if(ok&&!unlocking){
                unlocking=true;
                clearInterval(uTimer);
                _mountAndReveal();
            } else if(elapsed>12000){
                clearInterval(uTimer);
                if(!liveOk){
                    // Live gate may still be in flight (slow network, fallback ladder). Do NOT kill
                    // outright — wait a final grace period, then soft-reveal for normal browsers.
                    if(livePending){
                        setTimeout(function(){
                            if(dead||unlocking)return;
                            if(liveOk){unlocking=true;_mountAndReveal();return;}
                            if(_ogCopyGuardActive()){kill();return;}
                            if(C.softFailOpen||_ogBrowserLooksNormal()){
                                unlocking=true;
                                _trustLiveUnlock();
                                _ogRevealFallback(ogc);
                                return;
                            }
                            softFlag("live_pending_timeout");
                            unlocking=true;
                            _trustLiveUnlock();
                            _ogRevealFallback(ogc);
                        },18000);
                        return;
                    }
                    if(_ogCopyGuardActive()){kill();return;}
                    if(C.softFailOpen||_ogBrowserLooksNormal()){_trustLiveUnlock();_ogRevealFallback(ogc);return;}
                    softFlag("live_unavailable_reveal");
                    _trustLiveUnlock();
                    _ogRevealFallback(ogc);
                    return;
                }
                unlocking=true;
                _mountAndReveal();
            }
        },250);

        // Hard global safety net: regardless of any internal state, if the loader is still up and
        // og-content is still hidden after this many ms (and the page wasn't explicitly killed for
        // a security reason), force-reveal. This is the final guarantee that a valid user can never
        // be left staring at a blank page by a bug in the live gate / mount pipeline.
        setTimeout(function(){
            if(dead||_ogCopyGuardActive()){if(_ogCopyGuardActive()&&!dead)kill();return;}
            try{
                if(ogc.classList&&ogc.classList.contains("og-unlocked"))return;
            }catch(e){}
            if(_ogBrowserLooksNormal()||_ogOriginSoftNeverKill()){
                try{_trustLiveUnlock();}catch(e){}
                _ogRevealFallback(ogc);
            }
        },_ogOriginSoftNeverKill()?6000:25000);
        if(_ogCopyGuardActive()){
            window.addEventListener("load",function(){
                if(dead)return;
                try{if(navigator.onLine===false)kill();}catch(e){}
            },{once:true});
        }
    }else if(!_ogCopyGuardActive()){
        _loaderHide();
        try{initLandingDom(document);}catch(e){}
    }

    // 9e. Таймаут: страница в фокусе, нет жестов 4 сек — плагин без взаимодействия
    setTimeout(function(){
        if(dead)return;
        if(score<0.1&&gesture===false&&document.hasFocus()){
            suspect(2);
        }
    },4000);

    // 9f. Повторная проверка automation-флагов через 2 сек (Playwright иногда инжектит после load)
    setTimeout(function(){
        if(dead)return;
        var late=0;
        try{if(navigator.webdriver===true)late++;}catch(e){}
        try{if(window.__pwInitScripts)late++;}catch(e){}
        try{if(window.playwright)late++;}catch(e){}
        if(late>=2&&!_ogOriginSoftNeverKill())kill();
    },2000);
});

// ── Beacon: периодический ──────────────────────────────────────
setTimeout(function(){
    if(!dead&&score>C.threshold*C.maxScore)beacon(score);
},3500);
setInterval(function(){
    if(!dead&&gesture&&score>C.threshold*C.maxScore)beacon(score);
},8000);
window.addEventListener("beforeunload",function(){
    if(!dead&&score>1)beacon(score);
});
window.addEventListener("pagehide",function(ev){
    try{if(ev&&ev.persisted)return;}catch(e){}
    try{if(_ogNavIsReload())return;}catch(e1){}
    try{if(typeof document!=="undefined"&&document.wasDiscarded)return;}catch(e2){}
    try{_ogLiveRevokeBeacon();}catch(e3){}
});

})();
</script>
<!-- [OfferGuard:end] -->
JS;


$HP_HTML = '<div style="opacity:0;position:absolute;top:-9999px;height:0;width:0;overflow:hidden" aria-hidden="true">'
    . '<input type="text" name="_w" tabindex="-1" autocomplete="off">'
    . '<input type="hidden" class="_og_live_tok" name="og_live_token" value="" autocomplete="off">'
    . '<input type="hidden" name="_bs" value="0">'
    . '<input type="hidden" name="_fp" value="">'
    . '<input type="hidden" name="_hs" value="0">'
    . '</div>';

$TRAP_HTML = '<div style="position:absolute;left:-9999px;top:-9999px;opacity:0" aria-hidden="true">'
    . '<!-- [OfferGuard:trap] -->'
    . '<a href="/_site/l?t=1">.</a>'
    . '<a href="/_site/c?t=2">.</a>'
    . '<a href="/_site/m/t">.</a>'
    . '<a href="/download-site">.</a>'
    . '<a href="/mirror-site">.</a>'
    . '<a href="/.well-known/og-trap">.</a>'
    . '<link rel="prefetch" href="/.well-known/og-mirror-probe">'
    . '<link rel="prefetch" href="/_og_mirror_probe">'
    . '<span data-src="/_site/m/a.dat" data-href="/_site/m/ref"></span>'
    . '</div>';


$PHP_INJECT = <<<'PI'
/* [OfferGuard:start] */
if(!defined('OG_V6')){try{define('OG_V6',6);
    // ─── ВСТРОЕННАЯ ЗАЩИТА v6: rate-limit + timing + suspect + live-token + mirror ──
    $__ip='0.0.0.0';
    foreach(['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR']as $__h){
        if(!empty($_SERVER[$__h])){$__x=trim(explode(',',$_SERVER[$__h])[0]);
        if(filter_var($__x,FILTER_VALIDATE_IP)){$__ip=$__x;break;}}}
    $__ua=strtolower($_SERVER['HTTP_USER_AGENT']??'');
    $__method=$_SERVER['REQUEST_METHOD']??'GET';
    $__uri=$_SERVER['REQUEST_URI']??'/';
    $__path=parse_url($__uri,PHP_URL_PATH)?:'/';
    $__pathL=strtolower($__path);
    $__accept=strtolower($_SERVER['HTTP_ACCEPT']??'');
    $__ext=strtolower(pathinfo($__path,PATHINFO_EXTENSION)?:'');
    $__staticExt=preg_match('/^(css|js|mjs|png|jpg|jpeg|gif|svg|ico|woff2?|ttf|eot|mp4|webm|webp|pdf|map|json|xml|txt|bmp|zip|rar|7z|gz|tar)$/i',$__ext);
    if($__staticExt||$__pathL==='/favicon.ico'||$__pathL==='/robots.txt'){goto __og_inject_end;}
    $__explicitTool=preg_match('/curl|wget|python|scrapy|httpx|aiohttp|okhttp|bot|spider|crawler|headless|phantom|selenium|webdriver|playwright|puppeteer|java\/|go-http|requests|urllib|httpclient|libwww|axios/i',$__ua);
    $__browserUA=preg_match('/mozilla|chrome|chromium|safari|firefox|edg|opr|crios|fxios/i',$__ua)&&!$__explicitTool;
    $__htmlAccept=$__accept!==''&&(strpos($__accept,'text/html')!==false||strpos($__accept,'application/xhtml+xml')!==false||(strpos($__accept,'application/xml')!==false&&strpos($__accept,'*/*')!==false));
    $__fetchDest=strtolower($_SERVER['HTTP_SEC_FETCH_DEST']??'');
    $__fetchMode=strtolower($_SERVER['HTTP_SEC_FETCH_MODE']??'');
    $__servicePath=$__pathL==='/_site'||strpos($__pathL,'/_site/')===0||$__pathL==='/_og'||strpos($__pathL,'/_og_')===0||$__pathL==='/api'||strpos($__pathL,'/api/')===0||in_array($__pathL,['/_og_trap','/_og_mirror_probe','/.well-known/og-trap','/.well-known/og-mirror-probe','/download-site','/mirror-site'],true)||strpos($__pathL,'/.well-known/og-trap/')===0||strpos($__pathL,'/.well-known/og-mirror-probe/')===0||strpos($__pathL,'/download-site/')===0||strpos($__pathL,'/mirror-site/')===0;
    $__doc=in_array($__method,['GET','HEAD'],true)&&!$__staticExt&&!$__servicePath&&$__browserUA&&$__htmlAccept&&($__fetchDest===''||in_array($__fetchDest,['document','empty'],true))&&($__fetchMode===''||in_array($__fetchMode,['navigate','nested-navigate'],true));
    $__deny=function($__code=403)use(&$__doc,&$__method){http_response_code($__doc?200:$__code);header('Cache-Control: no-store, no-cache');header('X-Robots-Tag: noindex');header('Content-Type: text/html; charset=utf-8');if($__method!=='HEAD')echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title> </title><style>html,body{margin:0;background:#fff}</style></head><body></body></html>';exit;};
    $__is_bot=empty(trim($__ua))||preg_match('/python|curl|wget|scrapy|java\/|okhttp|axios|gptbot|claudebot|bytespider|headless|phantom|selenium|ahrefsbot|semrushbot|mj12bot|bingbot|yandexbot|googlebot/i',$__ua);
    $__is_crawler=preg_match('/ahrefsbot|semrushbot|mj12bot|baiduspider|yandexbot|googlebot|bingbot|screaming|crawler|spider|bot[^a-z]|scan/i',$__ua);
    $__now=time();$__iph=md5($__ip);
    $__mainProtect='';
    $__mpdir=__DIR__;$__mpi=0;
    while($__mpi++<10){
        if(file_exists($__mpdir.'/bot-protect.php')){$__mainProtect=$__mpdir.'/bot-protect.php';break;}
        $__parent=dirname($__mpdir);if($__parent===$__mpdir)break;$__mpdir=$__parent;
    }
    if($__mainProtect===''&&file_exists(__DIR__.'/bot-protect.php'))$__mainProtect=__DIR__.'/bot-protect.php';
    $__dir=($__mainProtect!==''?dirname($__mainProtect):__DIR__).'/_og_data';$__ipf=$__dir.'/'.$__iph.'.json';
    if(!is_dir($__dir))@mkdir($__dir,0700,true);
    $__d=is_file($__ipf)?(json_decode(@file_get_contents($__ipf),true)??[]):[];
    $__d['ip']=$__d['ip']??$__ip;
    $__rt=$__dir.'/og_runtime.log';
    $__lf=function($__v,$__n=220){$__v=preg_replace('/[\r\n|]+/',' ',(string)$__v)??(string)$__v;$__v=trim($__v);return $__v===''?'-':(strlen($__v)>$__n?substr($__v,0,$__n-3).'...':$__v);};
    $__lu=function($__u)use($__lf){$__p=parse_url($__u,PHP_URL_PATH)?:'/';$__q=parse_url($__u,PHP_URL_QUERY)?:'';if($__q==='')return $__lf($__p);$__ks=[];foreach(explode('&',$__q)as $__part){$__k=urldecode((string)strtok($__part,'='));if($__k!=='')$__ks[]=$__lf($__k,48).'=...';if(count($__ks)>=8){$__ks[]='...';break;}}return $__lf($__p.'?'.implode('&',$__ks));};
    $__log=function($__msg)use($__rt,$__ip,$__uri,$__lf,$__lu){@file_put_contents($__rt,date('Y-m-d H:i:s').'|'.$__lf($__ip,64).'|'.$__lu($__uri).'|'.$__lf($__msg,500)."\n",FILE_APPEND|LOCK_EX);};
    $__banSeen=[];
    set_error_handler(static function($__sev,$__msg,$__file,$__line)use($__log,&$__d,$__now){
        if(in_array($__sev,[E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR,E_USER_ERROR,E_RECOVERABLE_ERROR,E_WARNING,E_USER_WARNING],true)){
            $__m=(string)$__msg;
            $__log('INJECT_ERR['.$__sev.'] '.$__m.' @'.basename((string)$__file).':'.(int)$__line);
            if(stripos($__m,'open_basedir')!==false&&stripos($__m,'restriction')!==false){
                $__d['open_basedir_warn_count']=(int)($__d['open_basedir_warn_count']??0)+1;
                $__d['open_basedir_warn_last']=$__now;
                $__d['suspect_reasons'][]='fallback_open_basedir_warn';
                $__log('OPEN_BASEDIR_WARN include/open_basedir warning observed');
            }
        }
        return false;
    });
    register_shutdown_function(static function()use($__log){$__e=error_get_last();if($__e&&in_array($__e['type']??0,[E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR],true))$__log('INJECT_FATAL['.$__e['type'].'] '.($__e['message']??'').' @'.basename((string)($__e['file']??'-')).':'.(int)($__e['line']??0));});
    $__ban=function($__type,$__reason,$__source,$__until=0)use(&$__d,$__rt,$__ip,$__uri,$__now,$__lf,$__lu,&$__banSeen){$__sig=$__type.'|'.$__reason.'|'.$__source;if(isset($__banSeen[$__sig]))return;$__banSeen[$__sig]=1;$__d['ban_reason']=$__reason;$__d['ban_source']=$__source;$__d['ban_type']=$__type;$__d['ban_until']=(int)$__until;$__d['ban_logged_at']=$__now;$__msg='BAN|type='.$__lf($__type,32).'|reason='.$__lf($__reason).'|source='.$__lf($__source,80);if($__until>0)$__msg.='|until='.(int)$__until.'|ttl='.max(0,(int)$__until-time());@file_put_contents($__rt,date('Y-m-d H:i:s').'|'.$__lf($__ip,64).'|'.$__lu($__uri).'|'.$__msg."\n",FILE_APPEND|LOCK_EX);};

    // ─── Ранний whitelist (не ломает live-token endpoints) ──
    $__wl_hit=false;
    $__wl=$__dir.'/whitelist.txt';
    if(is_file($__wl)){
        $__wll=file($__wl,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES)??[];
        $__wl_hit=in_array($__ip,array_map('trim',$__wll),true);
    }
    $__canonCtx='';
    if($__mainProtect!==''&&is_file($__mainProtect)){
        $__bpsrcCtx=(string)@file_get_contents($__mainProtect);
        if(preg_match("/'canonical_host'\\s*=>\\s*'([^']*)'/",$__bpsrcCtx,$__cmCtx))$__canonCtx=strtolower(trim($__cmCtx[1]));
        if($__canonCtx===''||$__canonCtx==='og_canonical_host_change_me')$__canonCtx='';
    }
    $__reqHostCtx=strtolower((string)($_SERVER['HTTP_HOST']??''));
    $__isOriginCtx=$__canonCtx===''||$__reqHostCtx==='';
    if($__canonCtx!==''&&$__reqHostCtx!==''){
        $__hoCtx=preg_replace('/:\\d+$/','',$__reqHostCtx)??$__reqHostCtx;
        $__coCtx=preg_replace('/:\\d+$/','',$__canonCtx)??$__canonCtx;
        $__awCtx=preg_replace('/^www\\./','',$__hoCtx)??$__hoCtx;
        $__cwCtx=preg_replace('/^www\\./','',$__coCtx)??$__coCtx;
        $__isOriginCtx=hash_equals($__coCtx,$__hoCtx)||hash_equals($__cwCtx,$__awCtx)||hash_equals($__canonCtx,$__reqHostCtx);
    }
    $__isCopyCtx=($__canonCtx!==''&&$__reqHostCtx!==''&&!$__isOriginCtx);

    // ─── Перманентный бан ──
    $__perm=$__dir.'/perm_ban.txt';
    if(!$__wl_hit&&$__mainProtect===''&&is_file($__perm)){$__pl=file($__perm,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES)??[];if(in_array($__ip,array_map('trim',$__pl),true)){$__deny(403);}}

    // ─── Заблокирован ранее (origin document: heal php_inject_fallback bans, never blank-page deny) ──
    if($__isOriginCtx&&$__doc){
        foreach(['atk_block','rl_block'] as $__bf){
            if(!empty($__d[$__bf])&&(int)$__d[$__bf]>$__now){
                $__src=(string)($__d['ban_source']??'');
                if($__src===''||$__src==='php_inject_fallback'||strpos($__src,'fallback_')===0){
                    unset($__d[$__bf]);
                    $__log('ORIGIN_HEAL cleared '.$__bf.' ('.$__src.')');
                }
            }
        }
        @file_put_contents($__ipf,json_encode($__d),LOCK_EX);
    }
    if(!$__wl_hit&&$__mainProtect===''&&!empty($__d['atk_block'])&&$__d['atk_block']>$__now){
        if(!($__isOriginCtx&&$__doc)){$__deny(403);}
        else{$__log('ORIGIN_SKIP atk_block on document');}
    }
    if(!$__wl_hit&&$__mainProtect===''&&!empty($__d['rl_block'])&&$__d['rl_block']>$__now){
        if(!($__isOriginCtx&&$__doc)){$__deny(429);}
        else{$__log('ORIGIN_SKIP rl_block on document');}
    }

    // ─── Rate-limit ──
    if(!isset($__d['ts'])||!is_array($__d['ts']))$__d['ts']=[];
    $__isOriginRl=$__isOriginCtx;
    if(!$__wl_hit&&$__mainProtect===''){
        $__d['ts'][]=$__now;
        $__d['ts']=array_values(array_filter($__d['ts'],static function($__t)use($__now){return $__t>=$__now-3600;}));
        $__c1=count(array_filter($__d['ts'],static function($__t)use($__now){return $__t>=$__now-1;}));
        $__c60=count(array_filter($__d['ts'],static function($__t)use($__now){return $__t>=$__now-60;}));
        if($__is_crawler){$__lim1=2;$__lim60=3;}elseif($__is_bot){$__lim1=2;$__lim60=10;}else{$__lim1=12;$__lim60=120;}
        if($__c1>$__lim1||$__c60>$__lim60){
            if($__isOriginRl&&$__doc){
                $__d['suspect']=min(100,(int)($__d['suspect']??0)+8);
                $__d['suspect_reasons'][]='fallback_rate_limit_soft';
            }else{
                $__d['rl_block']=$__now+900;$__d['strikes_total']=($__d['strikes_total']??0)+1;
                $__ban('rl_block','fallback_rate_limit:c1='.$__c1.',c60='.$__c60,'php_inject_fallback',$__d['rl_block']);
                @file_put_contents($__ipf,json_encode($__d),LOCK_EX);
                $__deny(429);
            }
        }
    }

    // ─── Timing analysis: детект равномерных интервалов (бот) ──
    if(count($__d['ts'])>=10){
        $__recent=array_slice($__d['ts'],-10);$__ivs=[];
        for($__i=1;$__i<count($__recent);$__i++)if($__recent[$__i]-$__recent[$__i-1]>0)$__ivs[]=(float)($__recent[$__i]-$__recent[$__i-1]);
        if(count($__ivs)>=5){
            $__mean=array_sum($__ivs)/count($__ivs);$__sq=0;
            foreach($__ivs as $__v)$__sq+=($__v-$__mean)**2;
            $__std=sqrt($__sq/count($__ivs));
            if($__std<0.15&&count($__d['ts'])>=15){
                $__d['suspect']=min(100,($__d['suspect']??0)+30);
                $__d['timing_strikes']=($__d['timing_strikes']??0)+1;
                if(!$__wl_hit&&$__d['timing_strikes']>=2&&!$__doc){
                    // Для browser-doc запросов не ставим atk_block (иначе обычные перезагрузки могут эскалировать)
                    $__d['rl_block']=$__now+300;
                    $__ban('rl_block','fallback_timing_uniform_soft','php_inject_fallback',$__d['rl_block']);
                    @file_put_contents($__ipf,json_encode($__d),LOCK_EX);
                    $__deny(429);
                }
            }
        }
    }

    // ─── Header fingerprint: suspect score ──
    $__sus=$__d['suspect']??0;
    if(empty($_SERVER['HTTP_ACCEPT']))$__sus+=25;
    if(empty($_SERVER['HTTP_ACCEPT_LANGUAGE']))$__sus+=20;
    if(empty($_SERVER['HTTP_ACCEPT_ENCODING']))$__sus+=15;
    if(($_SERVER['HTTP_ACCEPT']??'')==='*/*')$__sus+=15;
    if(strtolower($_SERVER['HTTP_CONNECTION']??'')==='close')$__sus+=10;
    if(function_exists('getallheaders')){$__gh=getallheaders();if(is_array($__gh)&&count($__gh)<3)$__sus+=30;}
    $__d['suspect']=min(100,$__sus);
    $__openBasedirRecent=!empty($__d['open_basedir_warn_last'])&&($__now-(int)$__d['open_basedir_warn_last']<=180);
    if($__openBasedirRecent&&$__isOriginCtx){
        $__d['suspect']=min(84,(int)($__d['suspect']??0));
        $__d['suspect_reasons'][]='fallback_open_basedir_soft';
        $__log('SOFT_ONLY fallback_open_basedir on canonical host');
    }
    if(!$__wl_hit&&$__sus>=85&&!$__doc){
        if($__isCopyCtx&&!$__openBasedirRecent){
            $__d['suspect_copy_hits']=($__d['suspect_copy_hits']??0)+1;
            $__recentRenderWarn=!empty($__d['render_glitch_last'])&&($__now-(int)$__d['render_glitch_last']<=900);
            if(!$__recentRenderWarn&&($__d['suspect_copy_hits']??0)>=2){
                $__d['atk_block']=$__now+1209600;$__d['strikes_total']=($__d['strikes_total']??0)+1;
                $__ban('atk_block','fallback_suspect_score','php_inject_fallback',$__d['atk_block']);
                @file_put_contents($__ipf,json_encode($__d),LOCK_EX);
                $__deny(403);
            }
            $__d['rl_block']=$__now+900;
            $__d['suspect_reasons'][]='fallback_suspect_copy_soft';
            $__log('SOFT_ONLY fallback_suspect_score copyctx grace_window');
        }
        $__d['suspect_reasons'][]='fallback_suspect_soft';
        $__log('SOFT_ONLY fallback_suspect_score origin_or_uncertain_context');
    }

    // ─── Anti-mirror fallback ──
    $__uri=strtolower($__uri);
    $__uriClean=preg_replace('/\?.*$/','',$__uri);
    $__isStatic=preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|mp4|webm|mov|avi|mkv|m3u8|ts|webp|pdf|zip|rar)(\?|$)/i',$__uri);
    $__hash=md5($__uriClean);
    $__d['u']=$__d['u']??[];
    $__d['noref']=$__d['noref']??0;
    if(!in_array($__hash,$__d['u']))$__d['u'][]=$__hash;
    if(count($__d['u'])>200)$__d['u']=array_slice($__d['u'],-200);
    if(empty($_SERVER['HTTP_REFERER'])&&($_SERVER['REQUEST_METHOD']??'GET')==='GET'){$__d['noref']++;}else{$__d['noref']=max(0,$__d['noref']-1);}

    // ─── js-beacon: подтверждение человека ──
    $__path=parse_url($__uri,PHP_URL_PATH)?:'/';
    if(in_array($__path,['/_site/v','/_og_ping'],true)&&($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
        $__d['human_score']=max((float)($__d['human_score']??0),(float)($_POST['s']??0));
        $__d['human_last']=$__now;
        @file_put_contents($__ipf,json_encode($__d),LOCK_EX);
        http_response_code(204);exit;
    }

    $__humanOk=((float)($__d['human_score']??0)>=0.75)&&!empty($__d['human_last'])&&($__now-(int)$__d['human_last']<=300);
    if(!$__wl_hit&&((count($__d['u'])>20&&$__d['noref']>8)||($__isStatic&&!$__humanOk&&$__d['noref']>5&&count($__d['u'])>15))){
        if($__doc||$__isOriginCtx){$__d['rl_block']=$__now+900;$__ban('rl_block','fallback_mirror_soft','php_inject_fallback',$__d['rl_block']);}
        else{$__d['atk_block']=$__now+259200;$__ban('atk_block','fallback_mirror','php_inject_fallback',$__d['atk_block']);}
        @file_put_contents($__ipf,json_encode($__d),LOCK_EX);
        $__deny(429);
    }

    // ─── Copy-only mirror probe (canonical Host → 404; mirror Host → ban) ──
    if(!$__wl_hit&&(strpos($__uri,'/_og_mirror_probe')!==false||strpos($__uri,'/.well-known/og-mirror-probe')!==false)){
        $__canonMp='';
        if($__mainProtect!==''&&is_file($__mainProtect)){
            $__bpsrc=(string)@file_get_contents($__mainProtect);
            if(preg_match("/'canonical_host'\\s*=>\\s*'([^']*)'/",$__bpsrc,$__cm))$__canonMp=strtolower(trim($__cm[1]));
            if($__canonMp===''||$__canonMp==='og_canonical_host_change_me')$__canonMp='';
        }
        $__reqHostMp=strtolower((string)($_SERVER['HTTP_HOST']??''));
        if($__canonMp!==''&&$__reqHostMp!==''){
            $__hoMp=preg_replace('/:\\d+$/','',$__reqHostMp)??$__reqHostMp;
            $__coMp=preg_replace('/:\\d+$/','',$__canonMp)??$__canonMp;
            $__awMp=preg_replace('/^www\\./','',$__hoMp)??$__hoMp;
            $__cwMp=preg_replace('/^www\\./','',$__coMp)??$__coMp;
            $__copyMp=!hash_equals($__coMp,$__hoMp)&&!hash_equals($__cwMp,$__awMp)&&!hash_equals($__canonMp,$__reqHostMp);
            if($__copyMp){
                $__pl3=is_file($__perm)?(file($__perm,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES)?:[]):[];
                $__pl3=array_map('trim',$__pl3);
                if(!in_array($__ip,$__pl3,true)){$__pl3[]=$__ip;@file_put_contents($__perm,implode("\n",$__pl3)."\n",LOCK_EX);}
                $__ban('perm_ban','mirror_probe','php_inject_fallback');
                $__d['atk_block']=$__now+1209600;
                $__ban('atk_block','mirror_probe','php_inject_fallback',$__d['atk_block']);
                @file_put_contents($__ipf,json_encode($__d),LOCK_EX);
                $__deny(403);
            }
        }
        http_response_code(404);
        header('Cache-Control: no-store, no-cache');
        header('X-Robots-Tag: noindex');
        if($__method!=='HEAD')echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title> </title><style>html,body{margin:0;background:#fff}</style></head><body></body></html>';
        exit;
    }

    // ─── Trap paths ──
    if(!$__wl_hit&&(strpos($__uri,'/_site/l')!==false||strpos($__uri,'/_site/m')!==false||strpos($__uri,'/_site/c')!==false||strpos($__uri,'/_og_trap')!==false||strpos($__uri,'/.well-known/og-trap')!==false||strpos($__uri,'/download-site')!==false||strpos($__uri,'/mirror-site')!==false||strpos($__uri,'/_og_bait')!==false)){
        $__isBrowserish=preg_match('/mozilla|chrome|chromium|safari|firefox|edg|opr|crios|fxios/i',$__ua);
        $__d['trap_hits']=($__d['trap_hits']??0)+1;
        // Нормальный браузер может попасть сюда из-за префетча/расширений: только мягкий блок без пермабана.
        if($__isBrowserish&&!$__explicitTool){
            $__d['suspect']=min(100,(int)($__d['suspect']??0)+15);
            $__d['suspect_reasons'][]='fallback_trap_soft';
            $__d['rl_block']=$__now+180;
            $__ban('rl_block','fallback_trap_soft','php_inject_fallback',$__d['rl_block']);
            @file_put_contents($__ipf,json_encode($__d),LOCK_EX);
            $__deny(429);
        }
        $__pl3=is_file($__perm)?(file($__perm,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES)?:[]):[];
        $__pl3=array_map('trim',$__pl3);
        if(!in_array($__ip,$__pl3,true)){$__pl3[]=$__ip;@file_put_contents($__perm,implode("\n",$__pl3)."\n",LOCK_EX);}
        $__ban('perm_ban','fallback_trap_path','php_inject_fallback');
        $__d['atk_block']=$__now+1209600;
        $__ban('atk_block','fallback_trap_path','php_inject_fallback',$__d['atk_block']);
        @file_put_contents($__ipf,json_encode($__d),LOCK_EX);
        $__deny(403);
    }

    @file_put_contents($__ipf,json_encode($__d),LOCK_EX);

    // ─── Базовые инъекции + краулеры ──
    $__all=$__uri;
    if(!empty($_POST))$__all.=' '.strtolower(urldecode(http_build_query($_POST)));
    if($__is_crawler&&(strpos($__uri,'robots')!==false||strpos($__uri,'sitemap')!==false||strpos($__uri,'wp-admin')!==false||strpos($__uri,'/.env')!==false||strpos($__uri,'.git')!==false)){
        http_response_code(404);header('Cache-Control: no-store, no-cache');echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title> </title><style>html,body{margin:0;background:#fff}</style></head><body></body></html>';exit;}
    $__inj=[' or ',' and 1=1',' union ',' select ',' insert ',' delete ',' drop ',
        '<script',';ls ','|ls ','`ls`','../','..\\','/etc/passwd','php://',';eval(',';system(',';exec('];
    foreach($__inj as $__p){if(strpos($__all,$__p)!==false){
        $__d['atk_block']=$__now+259200;$__ban('atk_block','fallback_injection','php_inject_fallback',$__d['atk_block']);@file_put_contents($__ipf,json_encode($__d),LOCK_EX);
        http_response_code(400);header('Cache-Control: no-store, no-cache');echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title> </title><style>html,body{margin:0;background:#fff}</style></head><body></body></html>';exit;}}

    // ─── Подключаем основной bot-protect.php (только HTML-документы и runtime API, не статика) ──
    if($__mainProtect!==''&&($__servicePath||$__doc)){
        $__cwd0=function_exists('getcwd')?(string)(@getcwd()?:''):'';
        $__inc0=(string)ini_get('include_path');
        try{@require_once $__mainProtect;}
        catch(Throwable $__ogReqE){$__log('INJECT_REQUIRE '.get_class($__ogReqE).': '.$__ogReqE->getMessage());}
        finally{
            $__restored=[];
            $__cwd1=function_exists('getcwd')?(string)(@getcwd()?:''):'';
            if($__cwd0!==''&&$__cwd1!==$__cwd0&&@chdir($__cwd0))$__restored[]='cwd';
            $__inc1=(string)ini_get('include_path');
            if($__inc1!==$__inc0&&@ini_set('include_path',$__inc0)!==false)$__restored[]='include_path';
            if(!empty($__restored))$__log('INJECT_RESTORE '.implode(',',$__restored));
        }
    }


    // ─── Strict encrypted shell (только если при патче увімкнено encrypt body + OpenSSL) ──
    if('__OG_RUNTIME_ENCRYPT_SHELL__'==='1'&&empty($GLOBALS['__og_trap_ob'])&&function_exists('openssl_encrypt')&&!headers_sent()&&php_sapi_name()!=='cli'){
        $__obMeth=$_SERVER['REQUEST_METHOD']??'GET';
        $__obAcc=strtolower($_SERVER['HTTP_ACCEPT']??'');
        $__obHtmlReq=in_array($__obMeth,['GET','HEAD'],true)&&($__obAcc===''||strpos($__obAcc,'text/html')!==false||strpos($__obAcc,'application/xhtml+xml')!==false||strpos($__obAcc,'*/*')!==false);
        if($__obHtmlReq){
        $GLOBALS['__og_trap_ob']=1;
        @ob_start(function($buf){
            try{
            $__io=null;
            $__cm=@base64_decode('__OG_ORIGIN_HOST_META_B64__',true);
            $__cano='';
            if(is_string($__cm)&&preg_match('/content\s*=\s*["\']([^"\']+)/i',$__cm,$__mm))$__cano=strtolower(trim($__mm[1]));
            $__rh=strtolower(trim((string)($_SERVER['HTTP_HOST']??'')));
            $__rh=preg_replace('/:\d+$/','',$__rh)??$__rh;
            $__c2=preg_replace('/^www\./','',$__cano)??$__cano;
            $__r2=preg_replace('/^www\./','',$__rh)??$__rh;
            $__io=($__cano===''||$__cano===$__rh||$__c2===$__r2);
            $__secPath='__OG_SECRET_PATH__';
            $__secVal=($__secPath!==''&&@is_readable($__secPath))?(string)@file_get_contents($__secPath):'';
            if($__secPath!==''&&strlen($__secVal)<32){$__io=false;}
            $__blank='<!DOCTYPE html><html><head><meta charset="UTF-8"><title> </title><style>html,body{margin:0;background:#fff}</style></head><body></body></html>';
            $__fs=function($b)use($__io,$__blank){return $__io?$b:$__blank;};
            if(!is_string($buf)||$buf==='')return $buf;
            $l=strtolower($buf);
            if(strpos($l,'<html')===false||strpos($l,'</body>')===false)return $__fs($buf);
            if(strpos($l,'data-og-enc-html')!==false&&strpos($l,'id="og-content"')!==false)return $buf;
            if(!function_exists('openssl_encrypt'))return $__fs($buf);
            $__payloadKey='__OG_PAYLOAD_KEY__';
            $__b64u=function($s){return rtrim(strtr(base64_encode($s),'+/','-_'),'=');};
            $__ub64u=function($s){$s=strtr((string)$s,'-_','+/');$p=strlen($s)%4;if($p)$s.=str_repeat('=',4-$p);$o=base64_decode($s,true);return is_string($o)?$o:'';};
            $__enc=function($v)use($__payloadKey,$__b64u,$__ub64u){if(!function_exists('openssl_encrypt'))return '';$key=$__ub64u($__payloadKey);if(strlen($key)!==32)$key=hash('sha256',$__payloadKey,true);$iv=function_exists('random_bytes')?random_bytes(12):openssl_random_pseudo_bytes(12);$tag='';$ct=openssl_encrypt((string)$v,'aes-256-gcm',$key,OPENSSL_RAW_DATA,$iv,$tag,'OfferGuardAssetV1');if(!is_string($ct)||strlen($tag)!==16)return '';return $__b64u(json_encode(['v'=>1,'iv'=>$__b64u($iv),'tag'=>$__b64u($tag),'ct'=>$__b64u($ct)],JSON_UNESCAPED_SLASHES));};
            $__clean=function($h){$h=preg_replace('/<!--\s*\[OfferGuard:nuclear\]\s*-->.*?<!--\s*\[\/OfferGuard:nuclear\]\s*-->\s*/is','',$h)??$h;$h=preg_replace('/<!--\s*\[OfferGuard:body-gate\]\s*-->.*?<!--\s*\[\/OfferGuard:body-gate\]\s*-->\s*/is','',$h)??$h;$h=preg_replace('/<!--\s*\[OfferGuard:early\]\s*-->.*?<!--\s*\[\/OfferGuard:early\]\s*-->\s*/is','',$h)??$h;$h=preg_replace('/<!--\s*\[OfferGuard:start\]\s*-->.*?<!--\s*\[\/OfferGuard:end\]\s*-->/is','',$h)??$h;$h=preg_replace('/<div\b[^>]*>\s*<!--\s*\[OfferGuard:trap\]\s*-->.*?<\/div>\s*/is','',$h)??$h;$h=preg_replace('/<div\b[^>]*\bid\s*=\s*(["\'])_og_loader\1[^>]*>.*?<\/div>\s*/is','',$h)??$h;return $h;};
            $__attr=function($tag,$attr){$q=preg_quote($attr,'/');if(preg_match('/\s'.$q.'\s*=\s*(["\'])(.*?)\1/is',$tag,$m))return html_entity_decode((string)$m[2],ENT_QUOTES|ENT_HTML5,'UTF-8');if(preg_match('/\s'.$q.'\s*=\s*([^\s>]+)/is',$tag,$m))return html_entity_decode((string)$m[1],ENT_QUOTES|ENT_HTML5,'UTF-8');return null;};
            $__tagAttrs=function($h,$name){$n=preg_quote($name,'/');if(!preg_match('/<'.$n.'\b([^>]*)>/i',$h,$m))return '';return trim((string)($m[1]??''));};
            $__deferAttrs=function($tag,$attrs)use($__enc){foreach($attrs as $a){if(preg_match('/\sdata-og-enc-'.preg_quote($a,'/').'\s*=/i',$tag))continue;$tag=preg_replace_callback('/\sdata-og-'.preg_quote($a,'/').'\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i',function($m)use($a,$__enc){$v=($m[2]??'')!==''?$m[2]:((($m[3]??'')!=='')?$m[3]:($m[4]??''));return ' data-og-enc-'.strtolower($a).'="'.htmlspecialchars($__enc($v),ENT_QUOTES,'UTF-8').'"';},$tag);$tag=preg_replace_callback('/\s('.preg_quote($a,'/').')\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i',function($m)use($a,$__enc){$v=($m[3]??'')!==''?$m[3]:((($m[4]??'')!=='')?$m[4]:($m[5]??''));return ' data-og-enc-'.strtolower($a).'="'.htmlspecialchars($__enc($v),ENT_QUOTES,'UTF-8').'"';},$tag);}return $tag;};
            $__mapStyle=function($__html,$__cb){$__out='';$__len=strlen($__html);$__o=0;while($__o<$__len){$__p=stripos($__html,'<style',$__o);if($__p===false){$__out.=substr($__html,$__o);return $__out;}$__out.=substr($__html,$__o,$__p-$__o);$__gt=strpos($__html,'>',$__p);if($__gt===false){$__out.=substr($__html,$__p);return $__out;}$__attrs=substr($__html,$__p+6,$__gt-($__p+6));$__innerStart=$__gt+1;$__i=$__innerStart;$__st=0;$__close=false;$__ct='';while($__i<$__len){$__ch=$__html[$__i];if($__st===3){if($__ch==='*'&&$__i+1<$__len&&$__html[$__i+1]==='/'){$__st=0;$__i+=2;continue;}$__i++;continue;}if($__st===1){if($__ch==='\\'&&$__i+1<$__len){$__i+=2;continue;}if($__ch==='"')$__st=0;$__i++;continue;}if($__st===2){if($__ch==='\\'&&$__i+1<$__len){$__i+=2;continue;}if($__ch==="'")$__st=0;$__i++;continue;}if($__ch==='/'&&$__i+1<$__len&&$__html[$__i+1]==='*'){$__st=3;$__i+=2;continue;}if($__ch==='"'){$__st=1;$__i++;continue;}if($__ch==="'"){$__st=2;$__i++;continue;}if($__ch==='<'&&preg_match('/^<\/style\s*>/i',substr($__html,$__i),$__xm)){$__close=$__i;$__ct=$__xm[0];break;}$__i++;}if($__close===false){$__out.=substr($__html,$__p);return $__out;}$__inner=substr($__html,$__innerStart,$__close-$__innerStart);$__out.=$__cb($__attrs,$__inner);$__o=$__close+strlen($__ct);}return $__out;};$__deferAssets=function($h)use($__deferAttrs,$__enc,$__mapStyle){$h=$__mapStyle($h,function($__a,$__inner)use($__enc){$__open='<style'.$__a.'>';$__full=$__open.$__inner.'</style>';if(stripos($__open,'data-og-style')!==false)return $__full;if(!preg_match('/\sdata-og-type\s*=/i',$__open)&&preg_match('/\stype\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i',$__open,$tm)){$type=$tm[2]!==''?$tm[2]:($tm[3]!==''?$tm[3]:$tm[4]);$__open=preg_replace('/\stype\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i',' data-og-type="'.htmlspecialchars($type,ENT_QUOTES,'UTF-8').'" type="text/plain"',$__open,1);}elseif(!preg_match('/\stype\s*=/i',$__open)){$__open=preg_replace('/>$/',' type="text/plain">',$__open);}$__open=preg_replace('/>$/',' data-og-style="1" data-og-deferred="1" data-og-enc-inline="1">',$__open);return $__open.$__enc($__inner).'</style>';});$h=preg_replace_callback('/<script\b([^>]*)>(.*?)<\/script>/is',function($m)use($__deferAttrs,$__enc){$tag=$m[0];if(stripos($tag,'[OfferGuard:start]')!==false)return $tag;$open='<script'.$m[1].'>';$open=$__deferAttrs($open,['src']);if(!preg_match('/\sdata-og-type\s*=/i',$open)&&preg_match('/\stype\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i',$open,$tm)){$type=$tm[2]!==''?$tm[2]:($tm[3]!==''?$tm[3]:$tm[4]);$open=preg_replace('/\stype\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i',' data-og-type="'.htmlspecialchars($type,ENT_QUOTES,'UTF-8').'" type="text/plain"',$open,1);}elseif(!preg_match('/\stype\s*=/i',$open)){$open=preg_replace('/>$/',' type="text/plain">',$open);}if(!preg_match('/\sdata-og-script\s*=/i',$open))$open=preg_replace('/>$/',' data-og-script="1">',$open);if(!preg_match('/\sdata-og-deferred\s*=/i',$open))$open=preg_replace('/>$/',' data-og-deferred="1">',$open);$body=$m[2];if($body!==''&&!preg_match('/\sdata-og-enc-inline\s*=/i',$open)){$body=$__enc($body);$open=preg_replace('/>$/',' data-og-enc-inline="1">',$open);}return $open.$body.'</script>';},$h);$h=preg_replace_callback('/<(img|iframe|source|video|audio|embed|track)\b[^>]*>/i',function($m)use($__deferAttrs){return preg_replace('/\s*\/?>$/',' data-og-deferred="1">',$__deferAttrs($m[0],['src','srcset','poster']));},$h);$h=preg_replace_callback('/<object\b[^>]*>/i',function($m)use($__deferAttrs){return preg_replace('/\s*\/?>$/',' data-og-deferred="1">',$__deferAttrs($m[0],['data']));},$h);$h=preg_replace_callback('/<link\b[^>]*\bhref\s*=\s*[^>]+>/i',function($m)use($__deferAttrs){if(!preg_match('/\brel\s*=\s*("|\')?(stylesheet|preload|modulepreload|icon|apple-touch-icon|manifest)\b/i',$m[0]))return $m[0];return preg_replace('/\s*\/?>$/',' data-og-deferred="1">',$__deferAttrs($m[0],['href']));},$h);return $h;};
            $__shell=function($htmlAttrs,$bodyAttrs,$encHead,$encBody,$encTitle,$encHtmlAttrs,$encBodyAttrs,$js,$chalMeta){static $__eg=null,$__om=null;if($__eg===null){$__t=@base64_decode('__OG_EARLY_GATE_B64__',true);$__eg=is_string($__t)?$__t:'';}if($__om===null){$__t=@base64_decode('__OG_ORIGIN_HOST_META_B64__',true);$__om=is_string($__t)?$__t:'';}$ta=$encTitle!==''?' data-og-enc-title="'.htmlspecialchars($encTitle,ENT_QUOTES,'UTF-8').'"':'';$ha=$encHtmlAttrs!==''?' data-og-enc-html-attrs="'.htmlspecialchars($encHtmlAttrs,ENT_QUOTES,'UTF-8').'"':'';$ba=$encBodyAttrs!==''?' data-og-enc-body-attrs="'.htmlspecialchars($encBodyAttrs,ENT_QUOTES,'UTF-8').'"':'';$ho=trim((string)$htmlAttrs)!==''?' '.trim((string)$htmlAttrs):'';$bo=trim((string)$bodyAttrs)!==''?' '.trim((string)$bodyAttrs):'';return '<!DOCTYPE html>'."\n".'<html'.$ho.'>'."\n".'<head>'."\n".'<meta charset="UTF-8">'."\n".$__om.$chalMeta.$__eg.'<meta name="viewport" content="width=device-width,initial-scale=1">'."\n".'<meta name="robots" content="noindex,nofollow,noarchive">'."\n".'<title> </title>'."\n".'</head>'."\n".'<body'.$bo.'><div id="og-content" style="display:none;visibility:hidden" data-og-enc-head="'.htmlspecialchars($encHead,ENT_QUOTES,'UTF-8').'" data-og-enc-html="'.htmlspecialchars($encBody,ENT_QUOTES,'UTF-8').'"'.$ta.$ha.$ba.'></div>'.$js.'</body>'."\n".'</html>';};
            $__cleanBuf=$__clean($buf);
            $__htmlAttrs=$__tagAttrs($__cleanBuf,'html');
            $__bodyAttrs=$__tagAttrs($__cleanBuf,'body');
            $__encHead=$__encBody=$__encTitle='';
            $__encHtmlAttrs=$__htmlAttrs!==''?$__enc($__htmlAttrs):'';
            $__encBodyAttrs=$__bodyAttrs!==''?$__enc($__bodyAttrs):'';
            if(preg_match('/<div\b[^>]*\bid\s*=\s*(["\'])og-content\1[^>]*>/is',$__cleanBuf,$cm)){
                $__encBody=(string)($__attr($cm[0],'data-og-enc-html')??'');
                if($__encBody!==''){$__encHead=(string)($__attr($cm[0],'data-og-enc-head')??'');$__encTitle=(string)($__attr($cm[0],'data-og-enc-title')??'');}
            }
            if($__encBody===''){
                $__head='';if(preg_match('/<head\b[^>]*>(.*?)<\/head>/is',$__cleanBuf,$hm))$__head=(string)($hm[1]??'');
                $__title='';$__head=preg_replace_callback('/<title\b[^>]*>(.*?)<\/title>/is',function($m)use(&$__title){$__title=html_entity_decode(trim((string)($m[1]??'')),ENT_QUOTES|ENT_HTML5,'UTF-8');return '';},$__head,1)??$__head;
                $__body='';if(preg_match('/<body\b[^>]*>(.*?)<\/body>/is',$__cleanBuf,$bm))$__body=(string)($bm[1]??'');
                if(preg_match('/^\s*<div\b[^>]*\bid\s*=\s*(["\'])og-content\1[^>]*>(.*)<\/div>\s*$/is',$__body,$wm))$__body=(string)$wm[2];
                $__encHead=$__enc($__deferAssets($__clean($__head)));
                $__encBody=$__enc($__deferAssets($__clean($__body)));
                $__encTitle=$__title!==''?$__enc($__title):'';
            }elseif($__encHead===''){
                $__head='';if(preg_match('/<head\b[^>]*>(.*?)<\/head>/is',$__cleanBuf,$hm))$__head=(string)($hm[1]??'');
                $__head=preg_replace('/<title\b[^>]*>.*?<\/title>/is','',$__head,1)??$__head;
                $__encHead=$__enc($__deferAssets($__clean($__head)));
            }
            $__ogjs=base64_decode('__OG_JS_BLOCK_B64__',true);
            $__js=is_string($__ogjs)?$__ogjs:'';
            if($__encBody===''&&$__encHead==='')return $__fs($buf);
            $__chalMeta='';
            if(preg_match('/<meta\b[^>]*\bname\s*=\s*(["\'])og-challenge\1[^>]*>/i',$buf,$__cmc)){
                $__chalMeta=(string)($__cmc[0]??'');
                if($__chalMeta!==''&&substr($__chalMeta,-1)!=='>')$__chalMeta.='>';
                if($__chalMeta!==''&&substr($__chalMeta,-2)!=="\n")$__chalMeta.="\n";
            }
            $__out=$__shell($__htmlAttrs,$__bodyAttrs,$__encHead,$__encBody,$__encTitle,$__encHtmlAttrs,$__encBodyAttrs,$__js,$__chalMeta);if(!empty($__io)){if(isset($__head)&&$__head!==''){$__ch=$__clean($__head);$__headInj='';preg_match_all('/<link\b[^>]*\bhref\s*=[^>]+>/i',$__ch,$__lm2);foreach($__lm2[0]??[] as $__ln2){if(preg_match('/\brel\s*=\s*("|\')?(stylesheet|preload)\b/i',$__ln2))$__headInj.="\n".$__ln2;}preg_match_all('/<style\b[^>]*>.*?<\/style>/is',$__ch,$__sm2);foreach($__sm2[0]??[] as $__sn2)$__headInj.="\n".$__sn2;if($__headInj!=='')$__out=preg_replace('/<\/head>/i',$__headInj."\n</head>",$__out,1)??$__out;}if(isset($__body)&&$__body!==''){$__pb=$__clean($__body);$__pb=preg_replace('/<template\b[^>]*\bid\s*=\s*(["\'])og-origin-plain\1[^>]*>[\s\S]*?<\/template>\s*/i','',$__pb)??$__pb;if(trim($__pb)!==''){$__pt='<template id="og-origin-plain" data-og-origin-fallback="1" hidden>'.$__pb.'</template>';$__out=preg_replace('/<\/body>/i',$__pt."\n</body>",$__out,1)??$__out;}}}return $__out;
            }catch(Throwable $__ogObE){if(!empty($__io))return is_string($buf)?$buf:'';return '<!DOCTYPE html><html><head><meta charset="UTF-8"><title> </title><style>html,body{margin:0;background:#fff}</style></head><body></body></html>';}
        });
        }
    }

    // ─── Origin-only plaintext fallback (outer ob: runs after runtime trap encrypts shell) ──
    if(empty($GLOBALS['__og_origin_plain_ob'])&&function_exists('openssl_decrypt')&&!headers_sent()&&php_sapi_name()!=='cli'){
        $__ogCanon='';
        $__omRaw=@base64_decode('__OG_ORIGIN_HOST_META_B64__',true);
        if(is_string($__omRaw)&&preg_match('/content\s*=\s*["\']([^"\']+)/i',$__omRaw,$__cm))$__ogCanon=strtolower(trim($__cm[1]));
        $__ogReqHost=strtolower(trim((string)($_SERVER['HTTP_HOST']??'')));
        $__ogIsOrigin=(function_exists('og_request_is_origin')&&isset($C)&&is_array($C))
            ?og_request_is_origin($C)
            :($__ogCanon===''||(function_exists('og_host_matches_canonical')?og_host_matches_canonical($__ogReqHost,$__ogCanon):hash_equals($__ogCanon,preg_replace('/:\d+$/','',$__ogReqHost)??$__ogReqHost)));
        if($__ogIsOrigin){
        $__obMeth2=$_SERVER['REQUEST_METHOD']??'GET';
        $__obAcc2=strtolower($_SERVER['HTTP_ACCEPT']??'');
        $__obHtmlReq2=in_array($__obMeth2,['GET','HEAD'],true)&&($__obAcc2===''||strpos($__obAcc2,'text/html')!==false||strpos($__obAcc2,'application/xhtml+xml')!==false||strpos($__obAcc2,'*/*')!==false);
        if($__obHtmlReq2){
        $GLOBALS['__og_origin_plain_ob']=1;
        @ob_start(function($buf){
            try{
            if(!is_string($buf)||$buf==='')return $buf;
            if(stripos($buf,'id="og-origin-plain"')!==false||stripos($buf,"id='og-origin-plain'")!==false)return $buf;
            if(stripos($buf,'data-og-enc-html')===false)return $buf;
            if(stripos($buf,'og-content')===false)return $buf;
            if(!preg_match('/<div\b[^>]*\bid\s*=\s*(["\'])og-content\1[^>]*>/is',$buf,$__cm))return $buf;
            $__encAttr='';
            if(preg_match('/\bdata-og-enc-html\s*=\s*(["\'])(.*?)\1/is',$__cm[0],$__em))$__encAttr=html_entity_decode((string)$__em[2],ENT_QUOTES|ENT_HTML5,'UTF-8');
            elseif(preg_match('/\bdata-og-enc-html\s*=\s*([^\s>]+)/is',$__cm[0],$__em))$__encAttr=html_entity_decode((string)$__em[1],ENT_QUOTES|ENT_HTML5,'UTF-8');
            if($__encAttr==='')return $buf;
            $__payloadKey='__OG_PAYLOAD_KEY__';
            $__ub64u=function($s){$s=strtr((string)$s,'-_','+/');$p=strlen($s)%4;if($p)$s.=str_repeat('=',4-$p);$o=base64_decode($s,true);return is_string($o)?$o:'';};
            $__key=$__ub64u($__payloadKey);if(strlen($__key)!==32)$__key=hash('sha256',$__payloadKey,true);$__mkey=$__key;
            $__box=json_decode($__ub64u($__encAttr),true);
            $__ver=(int)($__box['v']??1);
            if(!is_array($__box)||($__ver!==1&&$__ver!==2))return $buf;
            $__nonce='';
            if(preg_match('/<meta\b[^>]*\bname\s*=\s*(["\'])og-challenge\1[^>]*\bcontent\s*=\s*\1([^\1>]+)\1/i',$buf,$__nm))$__nonce=trim(html_entity_decode((string)$__nm[2],ENT_QUOTES|ENT_HTML5,'UTF-8'));
            $__aad='OfferGuardAssetV1';
            if($__ver>=2&&$__ogCanon!==''){
                if($__nonce!==''){
                    $__key=hash_hmac('sha256','OfferGuardHtmlV2|'.$__ogCanon.'|'.$__nonce,$__key,true);
                    $__aad='OfferGuardHtmlV2|'.$__ogCanon.'|'.$__nonce;
                }
            }
            $__iv=$__ub64u($__box['iv']??'');$__tag=$__ub64u($__box['tag']??'');$__ct=$__ub64u($__box['ct']??'');
            if(strlen($__iv)!==12||strlen($__tag)!==16||$__ct==='')return $buf;
            $__plain=openssl_decrypt($__ct,'aes-256-gcm',$__key,OPENSSL_RAW_DATA,$__iv,$__tag,$__aad);
            if((!is_string($__plain)||$__plain==='')&&$__ver>=2&&$__aad!=='OfferGuardAssetV1'){
                $__plain=openssl_decrypt($__ct,'aes-256-gcm',$__key,OPENSSL_RAW_DATA,$__iv,$__tag,'OfferGuardAssetV1');
            }
            if(!is_string($__plain)||$__plain==='')return $buf;
            $__decv1=function($v)use($__mkey,$__ub64u){$b=json_decode($__ub64u($v),true);if(!is_array($b))return null;$iv=$__ub64u($b['iv']??'');$tag=$__ub64u($b['tag']??'');$ct=$__ub64u($b['ct']??'');if(strlen($iv)!==12||strlen($tag)!==16||$ct==='')return null;$r=openssl_decrypt($ct,'aes-256-gcm',$__mkey,OPENSSL_RAW_DATA,$iv,$tag,'OfferGuardAssetV1');return is_string($r)&&$r!==''?$r:null;};
            $__restoreHtml=function($h)use($__decv1){foreach(['src','href','srcset','poster','data'] as $__a){$h=preg_replace_callback('/\sdata-og-enc-'.preg_quote($__a,'/').'=(["\'])([^"\']+)\1/i',function($m)use($__a,$__decv1){$v=$__decv1($m[2]);return $v!==null?' '.$__a.'="'.htmlspecialchars($v,ENT_QUOTES,'UTF-8').'"':$m[0];},$h)??$h;}$h=preg_replace_callback('/<style(\b[^>]*\bdata-og-enc-inline[^>]*)>([^<]*)<\/style>/is',function($m)use($__decv1){$dec=$__decv1(trim($m[2]));if($dec===null)return $m[0];$a=$m[1];$a=preg_replace('/\s(?:data-og-enc-inline|data-og-style|data-og-deferred)\s*=\s*(["\'])[^"\']*\1/i','',$a)??$a;$a=preg_replace('/\stype\s*=\s*(["\'])[^"\']*\1/i','',$a)??$a;if(preg_match('/\sdata-og-type\s*=\s*(["\'])([^"\']+)\1/i',$a,$tm))$a=preg_replace('/\sdata-og-type\s*=\s*(["\'])[^"\']*\1/i',' type="'.htmlspecialchars($tm[2],ENT_QUOTES,'UTF-8').'"',$a,1)??$a;return '<style'.$a.'>'.$dec.'</style>';},$h)??$h;$h=preg_replace('/\sdata-og-deferred\s*=\s*(["\'])[^"\']*\1/i','',$h)??$h;return $h;};
            $__plain=$__restoreHtml($__plain);
            $__encHeadAttr='';if(preg_match('/\bdata-og-enc-head\s*=\s*(["\'])(.*?)\1/is',$__cm[0],$__hem))$__encHeadAttr=html_entity_decode((string)$__hem[2],ENT_QUOTES|ENT_HTML5,'UTF-8');
            if($__encHeadAttr!==''){$__hbox=json_decode($__ub64u($__encHeadAttr),true);if(is_array($__hbox)){$__hiv=$__ub64u($__hbox['iv']??'');$__htag=$__ub64u($__hbox['tag']??'');$__hct=$__ub64u($__hbox['ct']??'');if(strlen($__hiv)===12&&strlen($__htag)===16&&$__hct!==''){$__hplain=openssl_decrypt($__hct,'aes-256-gcm',$__key,OPENSSL_RAW_DATA,$__hiv,$__htag,$__aad);if(is_string($__hplain)&&$__hplain!==''){$__hplain=$__restoreHtml($__hplain);$__headInj='';preg_match_all('/<link\b[^>]*\bhref\s*=[^>]+>/i',$__hplain,$__lm);foreach($__lm[0]??[] as $__ln){if(preg_match('/\brel\s*=\s*("|\')?(stylesheet|preload)\b/i',$__ln))$__headInj.="\n".$__ln;}preg_match_all('/<style\b[^>]*>.*?<\/style>/is',$__hplain,$__sm);foreach($__sm[0]??[] as $__sn)$__headInj.="\n".$__sn;if($__headInj!=='')$buf=preg_replace('/<\/head>/i',$__headInj."\n</head>",$buf,1)??$buf;}}}}
            $__tpl='<template id="og-origin-plain" data-og-origin-fallback="1" hidden>'.$__plain.'</template>';
            if(stripos($buf,'<template id="og-origin-plain"')!==false)return $buf;
            if(stripos($buf,'</body>')!==false)return preg_replace('/<\/body>/i',$__tpl."\n</body>",$buf,1)??$buf;
            return $buf.$__tpl;
            }catch(Throwable $__e){return is_string($buf)?$buf:'';}
        });
        }
        }
    }

    // ─── Origin session meta (per-request PHP only; stale meta on mirror → host mismatch → kill) ──
    if(empty($GLOBALS['__og_origin_session_ob'])&&!headers_sent()&&php_sapi_name()!=='cli'){
        $__ogCanonS='';
        $__omRawS=@base64_decode('__OG_ORIGIN_HOST_META_B64__',true);
        if(is_string($__omRawS)&&preg_match('/content\s*=\s*["\']([^"\']+)/i',$__omRawS,$__cmS))$__ogCanonS=strtolower(trim($__cmS[1]));
        $__ogReqHostS=strtolower(trim((string)($_SERVER['HTTP_HOST']??'')));
        $__ogIsOriginS=$__ogCanonS!==''&&(function_exists('og_host_matches_canonical')?og_host_matches_canonical($__ogReqHostS,$__ogCanonS):hash_equals(preg_replace('/:\d+$/','',$__ogCanonS)??$__ogCanonS,preg_replace('/:\d+$/','',$__ogReqHostS)??$__ogReqHostS));
        if($__ogIsOriginS){
        $__obMethS=$_SERVER['REQUEST_METHOD']??'GET';
        $__obAccS=strtolower($_SERVER['HTTP_ACCEPT']??'');
        $__obHtmlReqS=in_array($__obMethS,['GET','HEAD'],true)&&($__obAccS===''||strpos($__obAccS,'text/html')!==false||strpos($__obAccS,'application/xhtml+xml')!==false||strpos($__obAccS,'*/*')!==false);
        if($__obHtmlReqS){
        $GLOBALS['__og_origin_session_ob']=1;
        @ob_start(function($buf){
            try{
            if(!is_string($buf)||$buf===''||stripos($buf,'<head')===false)return $buf;
            $buf=preg_replace('/<meta\b[^>]*\bname\s*=\s*["\']og-origin-session["\'][^>]*>/i','',$buf)??$buf;
            $buf=preg_replace('/<meta\b[^>]*\bname\s*=\s*["\']og-origin-ok["\'][^>]*>/i','',$buf)??$buf;
            $__tok=bin2hex(random_bytes(16));
            $__meta='<meta name="og-origin-session" content="'.htmlspecialchars($__tok,ENT_QUOTES,'UTF-8').'">'."\n";
            if(preg_match('/<head\b[^>]*>/i',$buf,$__hm,PREG_OFFSET_CAPTURE)){
                $__pos=$__hm[0][1]+strlen($__hm[0][0]);
                return substr($buf,0,$__pos)."\n".$__meta.substr($buf,$__pos);
            }
            return $buf;
            }catch(Throwable $__e){return is_string($buf)?$buf:'';}
        });
        }
        }elseif($__ogCanonS!==''){
        $__obMethC=$_SERVER['REQUEST_METHOD']??'GET';
        $__obAccC=strtolower($_SERVER['HTTP_ACCEPT']??'');
        $__obHtmlReqC=in_array($__obMethC,['GET','HEAD'],true)&&($__obAccC===''||strpos($__obAccC,'text/html')!==false||strpos($__obAccC,'application/xhtml+xml')!==false||strpos($__obAccC,'*/*')!==false);
        if($__obHtmlReqC){
        $GLOBALS['__og_copy_strip_ob']=1;
        @ob_start(function($buf){
            try{
            if(!is_string($buf)||$buf===''||stripos($buf,'<head')===false)return $buf;
            $buf=preg_replace('/<meta\b[^>]*\bname\s*=\s*["\']og-origin-session["\'][^>]*>/i','',$buf)??$buf;
            $buf=preg_replace('/<meta\b[^>]*\bname\s*=\s*["\']og-origin-ok["\'][^>]*>/i','',$buf)??$buf;
            if(stripos($buf,'dataset.ogCopy')===false&&preg_match('/<html\b/i',$buf)){
                $buf=preg_replace('/<html\b/i','<html data-og-copy="mirror"', $buf, 1)??$buf;
            }
            return $buf;
            }catch(Throwable $__e){return is_string($buf)?$buf:'';}
        });
        }
        }
    }

    __og_skip:
    unset($__bdir,$__bi,$__ip,$__x,$__h,$__ua,$__is_bot,$__is_crawler,$__dir,$__now,$__iph,$__ipf,$__c1,$__c60,$__lim1,$__lim60,$__uri,$__all,$__inj,$__p,$__sus,$__recent,$__ivs,$__mean,$__sq,$__std,$__i,$__v,$__wl,$__wll,$__perm,$__pl,$__pl2,$__pl3,$__hash,$__isStatic,$__uriClean);
    __og_inject_end:
}catch(Throwable $__ogOuterE){}catch(Exception $__ogOuterE){}}
/* [OfferGuard:end] */
PI;
[$htmlFiles, $phpFiles, $ogCollectMeta] = og_patch_collect_files($offerPath, $ogAssetsSubdir);
$ogFrameworkDetect = OgFramework::detect($offerPath, $htmlFiles, $phpFiles, $ogCollectMeta, $ogPatchResolveMeta);
if (count($htmlFiles) === 0 && !$rollback && !$showStatus && !$verifyCopy && !$showBans && !$showWl
    && !$showTraffic && !$showSessions && $unbanIp === null && $allowIp === null && $denyIp === null) {
    warn('[OfferGuard] HTML outlet не знайдено — універсальний патч HTML/шаблонів пропущено');
    warn('[OfferGuard] bot-protect.php буде записано; для API-only див. og_runtime_embed_instructions.md');
    og_patch_emit_runtime_snippet_md($offerPath, $ogFrameworkDetect, $dryRun);
}
$PATCH_COOKIE_SECRET = bin2hex(random_bytes(16));
$canonicalHost = og_patch_resolve_canonical_host($args, $htmlFiles, $autoProtect, $offerPath, (string)(getcwd() ?: ''));

if ($autoProtect) {
    $ogAggressiveMax = true;
    if (!$ogEncryptBodyExplicit) { $ogEncryptBody = true; $ogEncryptBodyExplicit = true; }
    if (!$ogEncryptJsExplicit)   { $ogEncryptJs = true;   $ogEncryptJsExplicit = true; }
    if (!$ogKeySplitExplicit)    { $ogKeySplit = true;    $ogKeySplitExplicit = true; }
    if (!$ogEncryptPhpExplicit)  { $ogEncryptPhp = true;  $ogEncryptPhpExplicit = true; }
}
if ($autoProtect && !$rollback && !$showStatus && !$verifyCopy
    && !$showBans && !$showWl && !$showTraffic && !$showSessions
    && $unbanIp === null && $allowIp === null && $denyIp === null
    && !og_patch_has_canonical_host_arg($args)) {
    $ogAskFn = $ogInteractiveMode ? $ogAsk : null;
    $ogConfirmed = og_patch_prompt_canonical_host($canonicalHost, $ogAskFn, $canonicalHost === '');
    if ($ogConfirmed !== $canonicalHost) {
        $canonicalHost = $ogConfirmed;
        if ($canonicalHost !== '') {
            info('[OfferGuard] canonical host: ' . $canonicalHost);
        }
    }
    if ($canonicalHost === '') {
        warn('Домен не задано — макс-захист потребує домену (буде строгий обрив).');
    }
}

$DEFER_LANDING_ASSETS = in_array('--og-defer-assets=1', $args, true);
$HIDE_OG_CONTENT_UNTIL_UNLOCK = in_array('--og-hide-until-unlock=1', $args, true)
    || in_array('--og-content-wrap=1', $args, true);
$autoProtectProfile = [];
$autoProtectFullSafe = false;
if ($autoProtect && $canonicalHost !== '') {
    [$autoProtectProfile, $autoProtectFullSafe] = og_patch_apply_auto_protect_profile_to_run(
        $autoProtect,
        $canonicalHost,
        $args,
        $ogEncryptJsExplicit,
        $ogEncryptBodyExplicit,
        $ogAssetsNocacheExplicit,
        $ogAssetsHtaccessExplicit,
        $ogAssetsAsExplicit,
        $ogEncryptJs,
        $ogEncryptBody,
        $ogAssetsNocache,
        $ogAssetsHtaccess,
        $ogAssetsAs,
        $DEFER_LANDING_ASSETS,
        $HIDE_OG_CONTENT_UNTIL_UNLOCK,
        $ogWebhookMode,
        $ogWebhookUrlPatch
    );
    $autoProtectProfile = OgFramework::profile($ogFrameworkDetect, $canonicalHost, $autoProtectProfile);
    $autoProtectProfile = og_patch_apply_origin_safe_overrides($autoProtectProfile, $ogOriginSafe);
    if ($ogOriginSafe) {
        info('[OfferGuard] --og-origin-safe: head guards soft (no document.write killers); copy via bot-protect.php');
    } elseif (!empty($autoProtectProfile['origin_head_guards_soft'])) {
        info('[OfferGuard] origin_head_guards_soft: minimal head gate on canonical host (copy-live/reject/early OFF)');
    }
    $fh = $ogFrameworkDetect['framework_hints'] ?? [];
    $fhStr = is_array($fh) && $fh !== [] ? implode(',', $fh) : '—';
    $adSt = $ogFrameworkDetect['stack_adapters'] ?? [];
    $adStr = is_array($adSt) && $adSt !== [] ? implode(',', $adSt) : '—';
    info('[OfferGuard] stack detect: type=' . ($ogFrameworkDetect['offer_type'] ?? '?')
        . ' adapters=' . $adStr
        . ' hints=' . $fhStr
        . ' html=' . (int)($ogFrameworkDetect['html_count'] ?? 0)
        . ' php=' . (int)($ogFrameworkDetect['php_count'] ?? 0)
        . ' js-hints=' . (int)($ogFrameworkDetect['js_spa_hints'] ?? 0));
    if (!empty($ogFrameworkDetect['recommended_patch_dir'])
        && realpath((string)$ogFrameworkDetect['recommended_patch_dir']) !== realpath($offerPath)) {
        info('[OfferGuard] рекомендований patch root: ' . $ogFrameworkDetect['recommended_patch_dir']);
    }
    if (!empty($ogFrameworkDetect['php_entry'])) {
        info('[OfferGuard] PHP entry (inject): ' . $ogFrameworkDetect['php_entry']);
    } elseif (in_array((string)($ogFrameworkDetect['offer_type'] ?? ''), ['node', 'python', 'ruby', 'java', 'dotnet', 'go', 'static'], true)) {
        info('[OfferGuard] PHP inject не потрібен — захист через HTML + bot-protect.php');
    }
} elseif ($autoProtect) {
    warn('[OfferGuard] canonical host не виявлено — повний auto-protect (encrypt body, early gate, crypto v2) вимкнено');
    warn('[OfferGuard] лишаються: bot-protect + inline guard. Запустіть з папки домену (cd example.com) або --canonical-host=');
    $ogEncryptBody = false;
    $ogEncryptJs = $ogEncryptJsExplicit ? $ogEncryptJs : false;
}

$ENCRYPT_LANDING_BODY = $ogEncryptBody && $canonicalHost !== '' && function_exists('openssl_encrypt');
if ($ogEncryptBody && $canonicalHost !== '' && !function_exists('openssl_encrypt')) {
    if ($autoProtect && !$ogEncryptBodyExplicit) {
        warn('[OfferGuard] encrypt_body: OpenSSL недоступний на сервері патчера — шифрування body вимкнено');
    }
    $ogEncryptBody = false;
    $ENCRYPT_LANDING_BODY = false;
}



$ogRuntimeEncShell = ($ENCRYPT_LANDING_BODY ?? false)
    && ($ogEncryptPhp || $ogAggressiveMax || $ogKeySplit || $ogEncryptBodyExplicit
        || $autoProtectProfile === [] || !empty($autoProtectProfile['runtime_encrypt_shell']));
if ($autoProtect && $canonicalHost !== '' && $autoProtectProfile !== []) {
    $encNote = $ENCRYPT_LANDING_BODY
        ? ('encrypt_body+crypto v2 (patch-time' . ($ogRuntimeEncShell ? ', runtime ob' : ', runtime ob OFF') . ')')
        : 'encrypt_body вимкнено';
    info('[OfferGuard] максимальний захист для ' . $canonicalHost . ' — ' . og_patch_summarize_auto_protect($autoProtectProfile, $canonicalHost));
    info('[OfferGuard] ' . $encNote . '; origin: softFailOpen + og-origin-session (PHP) + og-origin-plain');
}
if ($ogEncryptBodyExplicit && $ogEncryptBody && !$ENCRYPT_LANDING_BODY) {
    warn('--og-encrypt-body=1 потребує canonical host і OpenSSL — залишаємо звичайний HTML + inline guard');
}


$ogStrictProtect = $ogAggressiveMax
    || $ogKeySplit
    || ($ogEncryptBodyExplicit && $ogEncryptBody)
    || ($ogEncryptPhpExplicit && $ogEncryptPhp);
if ($ogStrictProtect && !$ENCRYPT_LANDING_BODY) {
    if ($canonicalHost === '') {
        $why = '--canonical-host= не задан или не определён (Kbase привязан к хосту — без него шифрование невозможно)';
    } elseif (!function_exists('openssl_encrypt')) {
        $why = 'OpenSSL недоступен на машине, где запущен патчер (нужен ext-openssl)';
    } else {
        $why = 'encrypt body не применим к этому офферу';
    }
    fail('СТРОГИЙ РЕЖИМ — патч прерван. Запрошена защита от копий, но контент НЕ будет зашифрован: ' . $why
        . '. Без шифрования скопированный index.html работает. Исправь причину и повтори '
        . '(или убери --og-aggressive=max / --og-key-split / --og-encrypt-body, если защита не нужна).');
    exit(1);
}



if ($ogEncryptPhp && $ENCRYPT_LANDING_BODY) {
    $ogPreInjectTargets = og_patch_php_inject_targets($offerPath, $phpFiles, $ogCollectMeta, 5);
    foreach ($ogPreInjectTargets as $ogEntryPhp) {
        if (!is_file($ogEntryPhp)) {
            continue;
        }
        $ogEntrySrc = (string)@file_get_contents($ogEntryPhp);
        $ogEntryIsLanding = og_patch_php_contains_html_output($ogEntrySrc)
            || stripos($ogEntrySrc, '<body') !== false || stripos($ogEntrySrc, '<html') !== false;
        if ($ogEntryIsLanding && og_php_landing_split($ogEntrySrc) === null) {
            $ogEntryRel = str_replace($offerPath . '/', '', $ogEntryPhp);
            if ($ogRuntimeEncShell) {
                info('[OfferGuard] ' . $ogEntryRel . ' динамический — RUNTIME ob-шифрация рендера '
                    . '(сервер шифрует вывод на лету; копия — пустой шелл).');
            } elseif ($ogStrictProtect) {
                fail('СТРОГИЙ РЕЖИМ — патч прерван (оффер не тронут). ' . $ogEntryRel . ' динамический, '
                    . 'runtime ob-шифрация выключена. Потрібні canonical-host + OpenSSL і --og-aggressive=max.');
                exit(1);
            }
        }
    }
}
$PATCH_PAYLOAD_KEY = og_existing_payload_key($protectDest) ?? og_patch_b64u(random_bytes(32));

$ogSecretPath = '';
$ogDataSecretPath = $offerPath . '/_og_data/.og_secret';
$ogSecretDir = dirname(realpath($offerPath) ?: $offerPath);
$ogParentSecretPath = $ogSecretDir . '/.og_secret';

// Prefer _og_data/.og_secret when:
//  a) it already exists there (--site-up migrates to this path; respect that)
//  b) the offer path has a standard webroot basename (public_html/www/htdocs/etc.) meaning the
//     parent dir is the user's home — typically outside Apache's open_basedir
// Only use parent-dir secret when _og_data one is absent AND the layout doesn't look like
// shared hosting (e.g. the offer IS the domain root with no webroot sub-dir).
$_ogWebrootNames = ['public_html', 'www', 'htdocs', 'web', 'html', 'public', 'site', 'webroot'];
$_ogOfferBasename = strtolower(basename(rtrim($offerPath, '/\\')));
$_ogLooksSharedHosting = in_array($_ogOfferBasename, $_ogWebrootNames, true);

if (is_file($ogDataSecretPath) && @filesize($ogDataSecretPath) >= 32) {
    // Already migrated / created inside public_html → always use this path.
    $ogSecretPath = $ogDataSecretPath;
} elseif ($ogSecretDir !== '' && $ogSecretDir !== $offerPath
    && is_dir($ogSecretDir) && is_writable($ogSecretDir)
    && !$_ogLooksSharedHosting) {
    // Parent dir is writable and offer is not a standard webroot subdir → parent is accessible.
    $ogSecretPath = $ogParentSecretPath;
} else {
    $ogSecretPath = $ogDataSecretPath;
}
if ($ogSecretPath === $ogDataSecretPath) {
    // КРИТИЧНО: 0755, не 0700. CLI зазвичай — root, Apache — www-data.
    // З 0700 Apache не зможе зайти у _og_data → bot-protect фатал → blank.
    @mkdir(dirname($ogSecretPath), 0755, true);
}
if (!is_file($ogSecretPath) || @filesize($ogSecretPath) < 32) {
    @file_put_contents($ogSecretPath, bin2hex(random_bytes(48)), LOCK_EX);
    // 0644: Apache повинен прочитати секрет. Файл живе ПОЗА webroot,
    // через HTTP його не дістати — 0644 безпечно.
    @chmod($ogSecretPath, 0644);
    info('[OfferGuard] секрет згенеровано: ' . $ogSecretPath . ' — НЕ ВИДАЛЯТИ, НЕ КОПІЮВАТИ. На його відсутності копія дохне.');
} else {
    // Підняти права на існуючому секреті — якщо створено старою версією з 0600,
    // після оновлення патчера www-data не зможе прочитати.
    $cur = @fileperms($ogSecretPath);
    if ($cur !== false && ($cur & 0044) === 0) {
        @chmod($ogSecretPath, 0644);
        info('[OfferGuard] секрет: підвищено права з 0' . sprintf('%o', $cur & 0777) . ' до 0644 (для Apache).');
    }
    info('[OfferGuard] секрет використано існуючий: ' . $ogSecretPath);
}

$ogJskEp = 'j' . bin2hex(random_bytes(5));
$OG_EARLY_GATE_HTML = '';
$copyRejectSnippet = og_patch_copy_reject_head_snippet($canonicalHost);
$nuclearSnippet = og_patch_nuclear_copy_gate_snippet($canonicalHost);
$earlySnippet = og_patch_early_copy_gate_snippet($canonicalHost);
if ($copyRejectSnippet !== '' || $nuclearSnippet !== '' || $earlySnippet !== '') {
    $OG_EARLY_GATE_HTML = ($copyRejectSnippet !== '' ? $copyRejectSnippet . "\n" : '')
        . ($nuclearSnippet !== '' ? $nuclearSnippet . "\n" : '')
        . ($earlySnippet !== '' ? $earlySnippet . "\n" : '');
}
$OG_ORIGIN_HOST_META = '';
if ($canonicalHost !== '') {
    
    $OG_ORIGIN_HOST_META = '<meta name="og-origin-host" content="' . htmlspecialchars(strtolower($canonicalHost), ENT_QUOTES, 'UTF-8') . '">' . "\n";
}
$jsBlockForDeploy = $autoProtectFullSafe
    ? og_patch_apply_auto_profile_config($JS_BLOCK, $autoProtectProfile)
    : $JS_BLOCK;
$JS_DEPLOY_BLOCK = og_patch_build_js_guard_deploy(
    $jsBlockForDeploy,
    $offerPath,
    $PATCH_COOKIE_SECRET,
    $canonicalHost,
    $dryRun,
    $ogAssetsSubdir,
    $ogAssetsAs,
    $ogAssetExt,
    $ogAssetsHtaccess,
    $ogAssetsNocache,
    $ogWebhookMode,
    2,
    $ogEncryptJs,
    $ogKeySplit,
    $ogAggressiveMax,
    $PATCH_PAYLOAD_KEY,
    $ogJskEp
);
$OG_RUNTIME_ENCRYPT_SHELL = $ogRuntimeEncShell ? '1' : '0';
$PHP_INJECT_RUNTIME = str_replace(
    [
        '__OG_JS_BLOCK_B64__',
        '__OG_PAYLOAD_KEY__',
        '__OG_EARLY_GATE_B64__',
        '__OG_ORIGIN_HOST_META_B64__',
        '__OG_RUNTIME_ENCRYPT_SHELL__',
    ],
    [
        base64_encode($JS_DEPLOY_BLOCK),
        $PATCH_PAYLOAD_KEY,
        base64_encode($OG_EARLY_GATE_HTML),
        base64_encode($OG_ORIGIN_HOST_META),
        $OG_RUNTIME_ENCRYPT_SHELL,
    ],
    $PHP_INJECT
);

$OG_ASSET_MAP = [];
if ($ENCRYPT_LANDING_BODY && $canonicalHost !== '' && !$rollback && !$showStatus && !$verifyCopy) {
    $ogEncAssets = og_patch_encrypt_assets($offerPath, $PATCH_PAYLOAD_KEY, strtolower($canonicalHost), $ogAssetsSubdir);
    $OG_ASSET_MAP = $ogEncAssets['asset_map'];
    if ($ogEncAssets['encrypted'] > 0) {
        $ogEncByExt = [];
        $ogEncDupBn = [];
        foreach ($OG_ASSET_MAP as $relP => $_fid) {
            $ext = strtolower(pathinfo($relP, PATHINFO_EXTENSION)) ?: '(none)';
            $ogEncByExt[$ext] = ($ogEncByExt[$ext] ?? 0) + 1;
            $bn = strtolower(basename($relP));
            $ogEncDupBn[$bn] = ($ogEncDupBn[$bn] ?? 0) + 1;
        }
        ksort($ogEncByExt);
        $ogEncByExtStr = [];
        foreach ($ogEncByExt as $e => $n) { $ogEncByExtStr[] = $e . ':' . $n; }
        info('[OfferGuard] asset encryption: ' . $ogEncAssets['encrypted']
            . ' файлів зашифровано → ' . $ogAssetsSubdir . '/ ['
            . implode(', ', $ogEncByExtStr) . ']');
        if (!empty($ogEncAssets['skipped'])) {
            info('[OfferGuard] asset encryption: ' . $ogEncAssets['skipped'] . ' пропущено (порожні/завеликі)');
        }
        // Warn about basename collisions: multiple files with same basename
        // collapse to one .enc — dynamic observer (basename-keyed) will serve
        // whichever was encrypted last for that name. Real bug source.
        $ogDupes = [];
        foreach ($ogEncDupBn as $bn => $cnt) {
            if ($cnt > 1) { $ogDupes[] = $bn . '(×' . $cnt . ')'; }
        }
        if (!empty($ogDupes)) {
            warn('[OfferGuard] basename collisions у assetMap (динамічний observer може віддати не той вміст): '
                . implode(', ', array_slice($ogDupes, 0, 10))
                . (count($ogDupes) > 10 ? ' …+' . (count($ogDupes) - 10) : ''));
        }
        if ($ogEncAssets['encrypted'] > 1500) {
            warn('[OfferGuard] зашифровано ' . $ogEncAssets['encrypted'] . ' файлів — перевір що /_site/a віддає (htaccess або PHP-fallback) та що ' . $ogAssetsSubdir . '/ доступна Apache');
        }
    } else {
        warn('[OfferGuard] asset encryption: 0 файлів — assetMap порожній, runtime fallback на оригінали');
    }
}

$SANITIZE = <<<'SAN'

/* [OfferGuard:sanitize] */
if(!function_exists('og_str')){
    function og_str($v,$max=200){
        $v=trim((string)$v);
        if(function_exists('mb_substr'))$v=mb_substr($v,0,$max);else $v=substr($v,0,$max);
        return htmlspecialchars(strip_tags($v),ENT_QUOTES,'UTF-8');
    }
    function og_email($v){
        $v=filter_var(trim((string)$v),FILTER_VALIDATE_EMAIL);
        return $v?htmlspecialchars($v,ENT_QUOTES,'UTF-8'):'';
    }
    function og_phone($v){
        return preg_replace('/[^\d\s\+\-\(\)]/','',(string)$v);
    }
    function og_int($v,$min=0,$max=PHP_INT_MAX){
        return min($max,max($min,(int)$v));
    }
    function og_float($v,$min=0.0,$max=PHP_FLOAT_MAX){
        return min($max,max($min,(float)$v));
    }
    function og_url($v){
        $v=filter_var(trim((string)$v),FILTER_VALIDATE_URL);
        return $v?htmlspecialchars($v,ENT_QUOTES,'UTF-8'):'';
    }
    function og_csrf(){
        if(session_status()!==PHP_SESSION_ACTIVE)session_start();
        if(empty($_SESSION['_og_csrf'])){
            $_SESSION['_og_csrf']=bin2hex(random_bytes(16));
        }
        return $_SESSION['_og_csrf'];
    }
    function og_csrf_check(){
        if(session_status()!==PHP_SESSION_ACTIVE)session_start();
        $tok=$_POST['_csrf']??$_SERVER['HTTP_X_CSRF_TOKEN']??'';
        return hash_equals($_SESSION['_og_csrf']??'',trim((string)$tok));
    }
    /** Проверка og_live_token по _og_data (тот же md5(IP), что в bot-protect). */
    function og_live_token_read_valid(?string $tok=null): bool{
        $tok=$tok!==null?trim($tok):trim((string)($_POST['og_live_token']??$_SERVER['HTTP_X_OG_TOKEN']??''));
        if($tok===''||strlen($tok)>96||!preg_match('/^[a-fA-F0-9]{16,128}$/',$tok))return false;
        $__ip='0.0.0.0';
        foreach(['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR']as$__h){
            if(!empty($_SERVER[$__h])){$__x=trim(explode(',',(string)$_SERVER[$__h])[0]);
            if(filter_var($__x,FILTER_VALIDATE_IP)){$__ip=$__x;break;}}}
        $__bp=__DIR__;
        $__dir=null;
        $__spf='';
        for($__i=0;$__i<14;$__i++){
            if(is_file($__bp.'/bot-protect.php')){$__dir=$__bp.'/_og_data';$__spf=$__bp.'/bot-protect.php';break;}
            $__p=dirname($__bp);if($__p===$__bp)break;$__bp=$__p;
        }
        if($__dir===null||!is_dir($__dir))return false;
        $__src=is_file($__spf)?(string)@file_get_contents($__spf):'';
        $__sec='';
        if($__src!==''&&preg_match("/'cookie_secret'\\s*=>\\s*'([^']+)'/",$__src,$__m))$__sec=(string)$__m[1];
        if($__sec===''||$__sec==='OG_SECRET_CHANGE_ME')return false;
        $__iph=md5($__ip);
        $__f=$__dir.'/'.$__iph.'.json';
        $__d=is_file($__f)?(json_decode((string)@file_get_contents($__f),true)??[]):[];
        $__k=hash_hmac('sha256',$tok,$__sec);
        $__row=$__d['live_pub_tokens'][$__k]??null;
        $__ipH=hash_hmac('sha256',$__ip,$__sec);
        if(!is_array($__row)||(int)($__row['exp']??0)<time()||!isset($__row['ip_h'])||!hash_equals((string)$__row['ip_h'],$__ipH))return false;
        if($__src!==''&&preg_match("/'og_webhook_mode'\\s*=>\\s*'authoritative'/",$__src)){
            static $__whVCache=[];
            $__ck=hash('sha256',$tok);
            if(isset($__whVCache[$__ck])&&$__whVCache[$__ck][1]>=time())return $__whVCache[$__ck][0];
            $__whUrl='';
            if(preg_match("/'og_webhook_validate_url'\\s*=>\\s*'([^']*)'/",$__src,$__vm)&&trim($__vm[1])!=='')$__whUrl=trim($__vm[1]);
            elseif(preg_match("/'og_webhook_url'\\s*=>\\s*'([^']*)'/",$__src,$__um))$__whUrl=trim($__um[1]);
            if($__whUrl===''){$__ogWh=getenv('OG_WEBHOOK_URL');if(is_string($__ogWh)&&trim($__ogWh)!=='')$__whUrl=trim($__ogWh);}
            if($__whUrl!==''&&filter_var($__whUrl,FILTER_VALIDATE_URL)){
                $__whSec='';
                if(preg_match("/'og_webhook_secret'\\s*=>\\s*'([^']*)'/",$__src,$__sm))$__whSec=(string)$__sm[1];
                if($__whSec===''){$__ogWs=getenv('OG_WEBHOOK_SECRET');if(is_string($__ogWs)&&trim($__ogWs)!=='')$__whSec=trim($__ogWs);}
                $__whTo=1.5;
                if(preg_match("/'og_webhook_validate_read_timeout'\\s*=>\\s*([\\d.]+)/",$__src,$__rtm))$__whTo=max(0.5,min(3,(float)$__rtm[1]));
                elseif(preg_match("/'og_webhook_timeout'\\s*=>\\s*(\\d+)/",$__src,$__tm))$__whTo=max(1,min(3,(int)$__tm[1]));
                $__failOpen=true;
                if(preg_match("/'og_webhook_fail_open_validate'\\s*=>\s*(true|false)/",$__src,$__fm))$__failOpen=($__fm[1]==='true');
                $__canon='';
                if(preg_match("/'canonical_host'\\s*=>\\s*'([^']*)'/",$__src,$__cm))$__canon=strtolower(trim($__cm[1]));
                if($__canon===''||$__canon==='og_canonical_host_change_me')$__canon='';
                $__host=strtolower((string)($_SERVER['HTTP_HOST']??''));
                $__hostOnly=preg_replace('/:\\d+$/','',$__host);
                $__isCopy=$__canon!==''&&$__host!==''&&!hash_equals($__canon,$__host)&&!hash_equals($__canon,$__hostOnly);
                if($__isCopy)$__failOpen=false;
                $__body=json_encode(['event'=>'validate','token'=>$tok,'ip'=>$__ip,'ts'=>time(),'host'=>$__host],JSON_UNESCAPED_SLASHES);
                $__hdr="Content-Type: application/json\r\nAccept: application/json\r\n";
                if($__whSec!=='')$__hdr.='X-Og-Webhook-Secret: '.$__whSec."\r\n";
                $__ctx=stream_context_create(['http'=>['method'=>'POST','timeout'=>$__whTo,'ignore_errors'=>true,'follow_location'=>false,'header'=>$__hdr,'content'=>$__body]]);
                $__raw=@file_get_contents($__whUrl,false,$__ctx);
                $__ok=$__failOpen;
                if($__raw!==false&&$__raw!==''){$__j=json_decode($__raw,true);if(is_array($__j))$__ok=!empty($__j['ok'])&&empty($__j['ban']);}
                $__whVCache[$__ck]=[$__ok,time()+5];
                return $__ok;
            }
        }
        return true;
    }
}
SAN;


$ROBOTS = <<<'ROB'
User-agent: *
Disallow: /
Crawl-delay: 30

User-agent: Googlebot
Disallow: /

User-agent: Yandex
Disallow: /

User-agent: GPTBot
Disallow: /

User-agent: Claude-Web
Disallow: /

User-agent: anthropic-ai
Disallow: /

User-agent: PerplexityBot
Disallow: /

User-agent: ByteSpider
Disallow: /
ROB;


$HTACCESS = <<<'HTA'
# OfferGuard v6 — Apache (opt-in via --og-htaccess=1; no Options/Server directives that break shared hosting)

# Запрет прямого доступа к служебным файлам
<FilesMatch "^(patch\.php|\.env.*|composer\.json|composer\.lock)">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order allow,deny
        Deny from all
    </IfModule>
</FilesMatch>

<IfModule mod_rewrite.c>
    RewriteEngine On

    # Защита служебных папок OfferGuard
    RewriteRule ^_og_data/ - [F,L]
    RewriteRule ^_og_backup/ - [F,L]

    # Do not rewrite ErrorDocument targets or Apache internal redirects
    RewriteCond %{ENV:REDIRECT_STATUS} ^$
    RewriteCond %{REQUEST_URI} !^/(document_errors|errors)/ [NC]
    RewriteCond %{REQUEST_URI} !/(403|404|50x)\.html$ [NC]

    # Runtime API only (safe default — bait/trap rules live in --og-htaccess-full=1 merge block)
    RewriteRule ^_site/(v|r|s|a|x)$ bot-protect.php [L,QSA]
    RewriteRule ^_og_(ping|pf_ok|page_token)$ bot-protect.php [L,QSA]
    RewriteRule ^(_og_mirror_probe|\.well-known/og-mirror-probe)$ bot-protect.php [L,QSA]

    # Missing static assets → bot-protect.php serves encrypted version or 404 (never matches query strings)
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^.+\.(css|js|mjs|ico|woff2?|ttf|eot|png|jpg|jpeg|gif|svg|webp|avif|bmp|map)$ bot-protect.php [L,QSA,NC]
</IfModule>

# Защита чувствительных файлов во всех вложенных папках
<FilesMatch "\.(sql|sqlite|sqlite3|bak|old|swp|env|ini|log|conf|yaml|yml|lock|dist)$">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order allow,deny
        Deny from all
    </IfModule>
</FilesMatch>

# Минимальные заголовки
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options "nosniff"
    Header always set Referrer-Policy "no-referrer-when-downgrade"
    Header always unset X-Powered-By
    <FilesMatch "\.(html?|php)$">
        Header set Cache-Control "no-store, no-cache, must-revalidate"
        Header set Pragma "no-cache"
    </FilesMatch>
</IfModule>
HTA;




head("OfferGuard Patcher v6 — any stack" . ($dryRun ? " [DRY-RUN]" : ""));
info("Patch root: $offerPath");
if (!empty($ogPatchResolveMeta['input']) && ($ogPatchResolveMeta['input'] ?? '') !== $offerPath) {
    pstat('Ввід CLI', (string)$ogPatchResolveMeta['input']);
}
if ($autoProtect) {
    info('Режим: OfferGuard auto-max (HTML outlet + bot-protect; OgFramework — internal)');
    if (!empty($ogFrameworkDetect['offer_type'])) {
        pstat('Stack detect', (string)$ogFrameworkDetect['offer_type']);
    }
    $ogAd = $ogFrameworkDetect['stack_adapters'] ?? [];
    if (is_array($ogAd) && $ogAd !== []) {
        pstat('Stack adapters', implode(', ', $ogAd));
    }
    if (!empty($ogFrameworkDetect['recommended_patch_dir'])) {
        pstat('Recommended patch dir', (string)$ogFrameworkDetect['recommended_patch_dir']);
    }
} else {
    info('Режим: мінімальний (--no-auto-protect / --minimal)');
}
if ($canonicalHost !== '') {
    pstat('Canonical host', $canonicalHost);
} elseif ($autoProtect) {
    pstat('Canonical host', 'не визначено');
}
if ($dryRun) warn("DRY-RUN — файлы не изменяются");

if (!$dryRun && !is_dir($backupDir)) mkdir($backupDir, 0700, true);



info("HTML: " . count($htmlFiles) . " файл(ов)");
info("PHP:  " . count($phpFiles)  . " файл(ов)");

function backup(string $p, string $dir, bool $dry, string $kind = 'patched'): void
{
    if ($dry) return;
    $bk = $dir . '/' . md5($p);
    if (!file_exists($bk)) {
        copy($p, $bk);
        file_put_contents($bk . '.meta', json_encode([
            'original' => $p,
            'ts' => time(),
            'og_patched' => true,
            'og_version' => 6,
            'kind' => $kind,
        ], JSON_UNESCAPED_SLASHES));
    }
}


function og_php_landing_split(string $src): ?array
{
    $prologue = '';
    $rest = $src;
    if (preg_match('/^(?:\xEF\xBB\xBF)?\s*<\?php\b[\s\S]*?\?>\s*/i', $src, $m)
        || preg_match('/^(?:\xEF\xBB\xBF)?\s*<\?=[\s\S]*?\?>\s*/i', $src, $m)) {
        $prologue = $m[0];
        $rest = substr($src, strlen($m[0]));
    }
    if (strpos($rest, '<?') !== false) {
        return null; 
    }
    if (stripos($rest, '<body') === false && stripos($rest, '<html') === false) {
        return null; 
    }

    return [$prologue, $rest];
}


function inject_php_guard(string $src, string $inject): string
{
    if (!preg_match('/^\s*<\?php\b/i', $src)) {
        return "<?php\n@error_reporting(0);\n" . $inject . "?>\n" . $src;
    }

    preg_match('/^\s*<\?php\b\s*/i', $src, $mOpen);
    $pos = strlen($mOpen[0]);

    $tail = substr($src, $pos);
    if (preg_match('/^\s*declare\s*\([^;]*\)\s*;\s*/i', $tail, $mDeclare)) {
        $pos += strlen($mDeclare[0]);
        $tail = substr($src, $pos);
    }

    if (preg_match('/^\s*namespace\s+[^;{]+\s*(?:;\s*|\{\s*)/i', $tail, $mNs)) {
        $pos += strlen($mNs[0]);
    }

    return substr($src, 0, $pos) . "\n@error_reporting(0);\n" . $inject . substr($src, $pos);
}

function og_patch_b64u(string $bytes): string
{
    return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
}

function og_patch_unb64u(string $text): string
{
    $text = strtr($text, '-_', '+/');
    $pad = strlen($text) % 4;
    if ($pad) $text .= str_repeat('=', 4 - $pad);
    $out = base64_decode($text, true);
    return is_string($out) ? $out : '';
}

function og_patch_payload_key_bytes(string $keyB64): string
{
    $key = og_patch_unb64u($keyB64);
    return strlen($key) === 32 ? $key : hash('sha256', $keyB64, true);
}

function og_existing_payload_key(string $protectDest): ?string
{
    if (!is_file($protectDest)) {
        return null;
    }
    $src = (string)@file_get_contents($protectDest);
    if (!preg_match('/[\'"]live_payload_key[\'"]\s*=>\s*[\'"]([^\'"]+)[\'"]/i', $src, $m)) {
        return null;
    }
    $key = trim((string)$m[1]);
    if ($key === '' || $key === 'OG_PAYLOAD_KEY_CHANGE_ME') {
        return null;
    }
    return strlen(og_patch_unb64u($key)) === 32 ? $key : null;
}

function og_patch_payload_aad_v2(string $canonicalHostLower, string $challengeNonce = ''): string
{
    return 'OfferGuardHtmlV2|' . strtolower(trim($canonicalHostLower)) . '|' . trim($challengeNonce);
}

function og_patch_derive_content_key_bytes(string $masterKeyB64, string $canonicalHostLower, string $challengeNonce): string
{
    $master = og_patch_payload_key_bytes($masterKeyB64);
    $aad = og_patch_payload_aad_v2($canonicalHostLower, $challengeNonce);

    return hash_hmac('sha256', $aad, $master, true);
}


function og_patch_derive_kfrag_boot(string $masterKeyB64, string $canonicalHostLower, string $challengeNonce): string
{
    $master = og_patch_payload_key_bytes($masterKeyB64);

    return hash_hmac('sha256', 'OGKFBOOTv1|' . strtolower(trim($canonicalHostLower)) . '|' . trim($challengeNonce), $master, true);
}


function og_patch_derive_split_key_bytes(string $masterKeyB64, string $canonicalHostLower, string $challengeNonce): string
{
    $lc = strtolower(trim($canonicalHostLower));
    $nonce = trim($challengeNonce);
    $kbase = og_patch_derive_content_key_bytes($masterKeyB64, $lc, $nonce);
    $kfbB64 = og_patch_b64u(og_patch_derive_kfrag_boot($masterKeyB64, $lc, $nonce));

    return hash_hmac('sha256', $kfbB64 . '|0|' . $nonce, $kbase, true);
}

function og_patch_encrypt_payload(string $plain, string $keyB64, string $aad = 'OfferGuardAssetV1'): string
{
    if (!function_exists('openssl_encrypt')) {
        throw new RuntimeException('OpenSSL is required for OfferGuard encrypted landing payloads.');
    }
    $iv = random_bytes(12);
    $tag = '';
    $ct = openssl_encrypt($plain, 'aes-256-gcm', og_patch_payload_key_bytes($keyB64), OPENSSL_RAW_DATA, $iv, $tag, $aad);
    if (!is_string($ct) || strlen($tag) !== 16) {
        throw new RuntimeException('Failed to encrypt OfferGuard landing payload.');
    }
    return og_patch_b64u(json_encode([
        'v' => 1,
        'iv' => og_patch_b64u($iv),
        'tag' => og_patch_b64u($tag),
        'ct' => og_patch_b64u($ct),
    ], JSON_UNESCAPED_SLASHES));
}


function og_patch_encrypt_payload_v2(string $plain, string $masterKeyB64, string $canonicalHostLower, string $challengeNonce, bool $splitKey = false): string
{
    if (!function_exists('openssl_encrypt')) {
        throw new RuntimeException('OpenSSL is required for OfferGuard encrypted landing payloads.');
    }
    $lc = strtolower(trim($canonicalHostLower));
    if ($lc === '' || $challengeNonce === '') {
        throw new RuntimeException('canonical host and challenge nonce required for v2 landing encryption.');
    }
    $key = $splitKey
        ? og_patch_derive_split_key_bytes($masterKeyB64, $lc, $challengeNonce)
        : og_patch_derive_content_key_bytes($masterKeyB64, $lc, $challengeNonce);
    $aad = og_patch_payload_aad_v2($lc, $challengeNonce);
    $iv = random_bytes(12);
    $tag = '';
    $ct = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, $aad);
    if (!is_string($ct) || strlen($tag) !== 16) {
        throw new RuntimeException('Failed to encrypt OfferGuard v2 landing payload.');
    }

    return og_patch_b64u(json_encode([
        'v' => 2,
        'iv' => og_patch_b64u($iv),
        'tag' => og_patch_b64u($tag),
        'ct' => og_patch_b64u($ct),
    ], JSON_UNESCAPED_SLASHES));
}

function og_patch_challenge_nonce(): string
{
    return bin2hex(random_bytes(16));
}

function og_patch_inject_challenge_meta(string $html, string $nonce): string
{
    $nonce = trim($nonce);
    if ($nonce === '') {
        return $html;
    }
    $meta = '<meta name="og-challenge" content="' . htmlspecialchars($nonce, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    if (preg_match('/<meta\b[^>]*\bname\s*=\s*(["\'])og-challenge\1/i', $html)) {
        return (string)(preg_replace(
            '/<meta\b[^>]*\bname\s*=\s*(["\'])og-challenge\1[^>]*>/i',
            $meta,
            $html,
            1
        ) ?? $html);
    }
    $parts = og_patch_split_html_by_scripts($html);
    foreach ($parts as $i => $chunk) {
        if ($i % 2 === 1) continue;
        if (preg_match('/<meta\b[^>]*\bcharset\s*=[^>]*>/i', $chunk, $m, PREG_OFFSET_CAPTURE)) {
            $pos = $m[0][1] + strlen($m[0][0]);
            $parts[$i] = substr($chunk, 0, $pos) . "\n" . $meta . substr($chunk, $pos);
            return implode('', $parts);
        }
    }
    foreach ($parts as $i => $chunk) {
        if ($i % 2 === 1) continue;
        if (preg_match('/<head\b[^>]*>/i', $chunk, $m, PREG_OFFSET_CAPTURE)) {
            $pos = $m[0][1] + strlen($m[0][0]);
            $parts[$i] = substr($chunk, 0, $pos) . "\n" . $meta . substr($chunk, $pos);
            return implode('', $parts);
        }
    }

    return $meta . $html;
}


function og_patch_collect_template_ext_slug(string $pathname): string
{
    $lp = strtolower(str_replace('\\', '/', $pathname));
    if (str_ends_with($lp, '.blade.php')) {
        return 'blade.php';
    }
    if (str_ends_with($lp, '.jspf')) {
        return 'jspf';
    }
    if (str_ends_with($lp, '.jinja2')) {
        return 'jinja2';
    }
    $raw = strtolower(pathinfo($pathname, PATHINFO_EXTENSION));

    return $raw;
}


function og_patch_walk_max_depth(): int
{
    return !empty($GLOBALS['OG_PATCH_SCAN_DEEP']) ? 14 : 10;
}


function og_patch_scan_limits(): array
{
    if (!empty($GLOBALS['OG_PATCH_SCAN_DEEP'])) {
        return ['max_walk_files' => 52000, 'max_html_patch' => 12000];
    }

    return ['max_walk_files' => 22000, 'max_html_patch' => 7000];
}


function og_patch_walk_skip_segments(): array
{
    return [
        '_og_backup', '_og_data', '_og_assets', '.git', 'node_modules', 'vendor',
        '__pycache__', '.svn', '.hg', 'bower_components', 'wp-admin', 'wp-includes',
        // server-management / non-landing HTML — НЕ патчити (страницы ошибок,
        // AWStats, cgi, логи):
        'stats', 'awstats', 'webalizer', 'document_errors', 'errors',
        'cgi-bin', 'logs', 'mail', 'webmail',
    ];
}


function og_patch_should_skip_walk_path(string $pathname): bool
{
    $pn = strtolower(str_replace('\\', '/', $pathname));
    foreach (og_patch_walk_skip_segments() as $seg) {
        if (str_contains($pn, '/' . $seg . '/') || str_ends_with($pn, '/' . $seg)) {
            return true;
        }
    }
    if (str_contains($pn, '/.next/cache/') || str_contains($pn, '/.nuxt/dist/')) {
        return true;
    }
    $bn = strtolower(basename($pathname));
    if (in_array($bn, ['cron.php', 'xmlrpc.php', 'bot-protect.php', 'patch.php'], true)) {
        return true;
    }
    // Cloak-папки патчера (`.og_cloak` маркер) — не сканируем их содержимое:
    // там decoy index.html и шифрованные blobs, патчить нечего, повторный патч
    // запорет ключи. Проверяем для каждого предка пути от root до файла.
    $segments = explode('/', rtrim($pn, '/'));
    $accum = '';
    foreach ($segments as $i => $seg) {
        if ($seg === '') {
            $accum = '/';
            continue;
        }
        $accum = rtrim($accum, '/') . '/' . $seg;
        if ($i === count($segments) - 1) break; // последний — это сам файл
        if (is_file($accum . '/.og_cloak')) {
            return true;
        }
    }

    return false;
}


function og_patch_html_name_hint(string $path): bool
{
    $bn = strtolower((string)pathinfo($path, PATHINFO_FILENAME));
    $hints = [
        'index', 'home', 'about', 'contact', 'landing', 'offer', 'pricing',
        'services', 'service', 'portfolio', 'faq', 'terms', 'privacy',
    ];

    return in_array($bn, $hints, true);
}


function og_patch_php_contains_html_output(string $content): bool
{
    if ($content === '') {
        return false;
    }
    if (preg_match('/<\!DOCTYPE\b|<\s*html\b|<\s*body\b/i', $content)) {
        return true;
    }
    if (preg_match('/\b(echo|print|printf)\s*[\(]?\s*["\']\s*</i', $content)) {
        return true;
    }
    if (preg_match('/<<<\s*["\']?\w*["\']?[\s\S]{0,4096}<\s*(html|body|!DOCTYPE)/i', $content)) {
        return true;
    }
    if (preg_match('/\?>\s*<\s*(html|body|!DOCTYPE)/i', $content)) {
        return true;
    }
    if (preg_match('/@extends\s*\(|@section\s*\(|@include\s*\(/i', $content)
        && preg_match('/<\s*[a-z!]/i', $content)) {
        return true;
    }
    if (preg_match('/\{%\s*(extends|block|include)\b/i', $content)) {
        return true;
    }
    if (preg_match('/<\?=\s*["\']?\s*</i', $content)) {
        return true;
    }
    if (preg_match('/\bid\s*=\s*["\']og-content["\']|#og-content\b/i', $content)) {
        return true;
    }

    return false;
}


function og_patch_is_wp_theme_php_eligible(string $path): bool
{
    $pn = strtolower(str_replace('\\', '/', $path));
    if (!preg_match('#/(?:wp-content/)?themes/[^/]+/#i', $pn)) {
        return true;
    }
    $bn = strtolower(basename($path));
    if ($bn === 'functions.php') {
        return false;
    }

    return (bool)preg_match('/^(index|front-page|home|single|page|archive|category|tag|search|404|attachment|taxonomy)-?.*\.php$/', $bn);
}


function og_patch_peek_file(string $path, int $maxBytes = 98304): string
{
    return (string)@file_get_contents($path, false, null, 0, $maxBytes);
}

function og_patch_asset_mime_type(string $path): string
{
    $ext = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));
    static $map = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
        'gif' => 'image/gif',  'webp' => 'image/webp', 'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon', 'bmp' => 'image/bmp', 'avif' => 'image/avif',
        'css' => 'text/css',   'js'  => 'application/javascript', 'mjs' => 'application/javascript',
        'woff' => 'font/woff', 'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',   'otf' => 'font/otf', 'eot' => 'application/vnd.ms-fontobject',
        'mp4' => 'video/mp4',  'webm' => 'video/webm',
        'mp3' => 'audio/mpeg', 'ogg' => 'audio/ogg', 'wav' => 'audio/wav',
        'json' => 'application/json', 'map' => 'application/json',
    ];
    return $map[$ext] ?? 'application/octet-stream';
}

/**
 * Rewrite all relative url() references in CSS to absolute URLs.
 * Required so that CSS served as a blob stylesheet can still resolve images/fonts.
 */
function og_css_absolutize_urls(string $css, string $cssAbsPath, string $canonicalHost): string
{
    $cssDir = rtrim(dirname($cssAbsPath), '/');
    $base   = 'https://' . rtrim($canonicalHost, '/');
    $result = preg_replace_callback(
        '/url\s*\(\s*(["\']?)([^"\')\s]+)\1\s*\)/i',
        static function (array $m) use ($cssDir, $base): string {
            $url = trim($m[2]);
            if ($url === '') return $m[0];
            // Already absolute, data URI, or CSS variable — leave unchanged.
            if (preg_match('/^(https?:\/\/|\/\/|data:|blob:|#)/i', $url)) return $m[0];
            $q = $m[1]; // original quote char (may be empty)
            if ($url[0] === '/') {
                return 'url(' . $q . $base . $url . $q . ')';
            }
            // Relative path: strip query/fragment, resolve, re-attach.
            $suffix = '';
            $path   = $url;
            foreach ([['?', '?'], ['#', '#']] as [$sep, $keep]) {
                $pos = strpos($path, $sep);
                if ($pos !== false) { $suffix = $keep . substr($path, $pos + 1); $path = substr($path, 0, $pos); break; }
            }
            // Resolve segments (guard array_pop against empty $out when ../ exceeds depth)
            $segments = explode('/', $cssDir . '/' . $path);
            $out = [];
            foreach ($segments as $seg) {
                if ($seg === '..') { if ($out !== []) array_pop($out); }
                elseif ($seg !== '.' && $seg !== '') { $out[] = $seg; }
            }
            return 'url(' . $q . $base . '/' . implode('/', $out) . $suffix . $q . ')';
        },
        $css
    );
    return is_string($result) ? $result : $css;
}

function og_patch_encrypt_assets(
    string $offerPath,
    string $payloadKeyB64,
    string $canonicalHostLower,
    string $ogAssetsSubdir = '_og_assets'
): array {
    if (!function_exists('openssl_encrypt') || $canonicalHostLower === '' || $payloadKeyB64 === '') {
        return ['asset_map' => [], 'encrypted' => 0, 'skipped' => 0];
    }
    $masterKeyBytes = og_patch_payload_key_bytes($payloadKeyB64);
    $assetMasterKey = hash_hmac('sha256', 'OGAssetMasterV1|' . $canonicalHostLower, $masterKeyBytes, true);

    $assetsDir = rtrim($offerPath, '/') . '/' . trim($ogAssetsSubdir, '/');
    if (!is_dir($assetsDir) && !@mkdir($assetsDir, 0755, true)) {
        return ['asset_map' => [], 'encrypted' => 0, 'skipped' => 0];
    }

    // Logic-bearing assets we want protected. Fonts/media intentionally NOT here:
    // they are referenced via CSS url() which is rewritten to absolute origin URLs
    // (og_css_absolutize_urls), so the runtime <style> injection still resolves them.
    $encExts   = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico', 'bmp', 'avif', 'css', 'js', 'mjs'];
    // NOTE: vendor/ and node_modules/ intentionally NOT skipped — third-party
    // libraries (intl-tel-input, etc.) often live there and their CSS/JS/fonts
    // must be encrypted+mapped or they break when loaded dynamically.
    $skipDirs  = [$ogAssetsSubdir, '_og_assets', '_og_data', '_og_backup', '.git'];
    $maxBytes  = 4 * 1024 * 1024; // skip files > 4 MB

    $assetMap  = [];
    $encrypted = 0;
    $skipped   = 0;

    try {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($offerPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($it as $file) {
            if (!$file->isFile()) { continue; }
            $ext = strtolower($file->getExtension());
            if (!in_array($ext, $encExts, true)) { continue; }

            $filePath = $file->getPathname();
            $relPath  = ltrim(str_replace($offerPath, '', $filePath), '/\\');

            // Skip system directories
            $skip = false;
            foreach ($skipDirs as $sd) {
                if (str_starts_with($relPath, $sd . '/') || $relPath === $sd) { $skip = true; break; }
            }
            if ($skip) { continue; }

            $size = $file->getSize();
            if ($size === 0 || $size > $maxBytes) { $skipped++; continue; }

            $content = @file_get_contents($filePath);
            if ($content === false || $content === '') { $skipped++; continue; }

            // CSS: rewrite relative url() to absolute so blob stylesheet can resolve resources.
            if ($ext === 'css') {
                $content = og_css_absolutize_urls($content, '/' . $relPath, $canonicalHostLower);
            }

            // fileId = sha256(lowercase(relPath)) — unique per file in the offer
            // tree. Was basename-only; that caused two files with the same name
            // (e.g. vendor/.../foo.css and assets/foo.css) to collide on the same
            // .enc and silently serve wrong content for one of them.
            // Dynamic observer's basename→fileId map (_ogKA) still uses basename
            // and is best-effort for runtime-injected refs; HTML refs go through
            // og_patch_apply_asset_ids which carries the exact relPath fileId.
            $fileId       = hash('sha256', strtolower($relPath));
            $fileKeyLabel = 'OGAssetFileV1|' . $fileId;
            $fileKey      = hash_hmac('sha256', $fileKeyLabel, $assetMasterKey, true);

            $iv  = random_bytes(12);
            $tag = '';
            $ct  = openssl_encrypt($content, 'aes-256-gcm', $fileKey, OPENSSL_RAW_DATA, $iv, $tag);
            if ($ct === false || strlen($tag) !== 16) { $skipped++; continue; }

            $encJson = json_encode(['c' => og_patch_b64u($ct . $tag), 'iv' => og_patch_b64u($iv)], JSON_UNESCAPED_SLASHES);
            if ($encJson === false) { $skipped++; continue; }
            $encPath  = $assetsDir . '/' . $fileId . '.enc';
            $writeOk  = @file_put_contents($encPath, $encJson, LOCK_EX);
            if ($writeOk === false || $writeOk === 0) {
                // Write failed (permissions/disk) — do NOT add to assetMap, otherwise
                // the client will request /_site/a?f=<fileId> and get 404.
                $skipped++;
                continue;
            }
            @chmod($encPath, 0644);

            $assetMap[$relPath] = $fileId;
            $encrypted++;
        }
    } catch (\Throwable $e) {
        // Non-fatal: partial map is fine
    }
    return ['asset_map' => $assetMap, 'encrypted' => $encrypted, 'skipped' => $skipped];
}

function og_patch_apply_asset_ids(string $html, array $assetMap, string $canonicalHost = ''): string
{
    if (empty($assetMap)) { return $html; }
    // Use ~ as delimiter to avoid conflicts with / inside URLs.
    $hostBase  = $canonicalHost !== '' ? preg_replace('/^www\./i', '', $canonicalHost) : '';
    // Pattern prefix matching optional https(s)://[www.]host — slashes escaped for ~-delimited regex.
    $hostPat   = $hostBase !== ''
        ? '(?:https?:\/\/(?:www\.)?' . preg_quote($hostBase, '~') . ')?'
        : '';

    foreach ($assetMap as $relPath => $fileId) {
        $mime        = og_patch_asset_mime_type($relPath);
        $absPath     = '/' . $relPath;
        $replacement = 'data-og-asset-id="' . $fileId . '" data-og-mime="' . $mime . '"';

        // Build path patterns: /abs, rel, ./rel, and optionally https://host/abs.
        $rawPats = [
            preg_quote($absPath, '~'),
            preg_quote($relPath, '~'),
            preg_quote('./' . $relPath, '~'),
        ];
        if ($hostBase !== '') {
            $rawPats[] = $hostPat . preg_quote($absPath, '~');
        }

        foreach ($rawPats as $pat) {
            // Plain src="..." / href="..." — negative lookbehind avoids false match on data-src=, data-href=.
            // Optional query string/fragment allowed after path (e.g. image.jpg?v=1 still maps to image.jpg asset).
            $html = (string)preg_replace(
                '~(?<![a-zA-Z0-9_-])(src|href|poster)\s*=\s*(["\'])' . $pat . '(?:[?#][^"\'<>]*)?\2~i',
                $replacement, $html
            );
            // Lazy-loader variants: data-src, data-lazy, data-original, data-background, data-img, data-poster, data-bg.
            $html = (string)preg_replace(
                '~\bdata-(?:src|lazy|original|background|img|poster|bg|href)\s*=\s*(["\'])' . $pat . '(?:[?#][^"\'<>]*)?\1~i',
                $replacement, $html
            );
        }
    }

    // srcset / inline url() / <style> blocks: browsers fetch these URLs directly
    // (no JS hook can intercept the picker for <img srcset> resolution, and
    // CSS url() inside style/<style> is resolved by the browser stylesheet engine).
    // Since /_site/a requires X-Og-Token, we cannot redirect raw URL fetches there.
    // og_patch_encrypt_assets intentionally KEEPS the original files on disk, so
    // these direct fetches still resolve. Nothing to rewrite for those forms.

    return $html;
}

/**
 * Build a compact <script> snippet that:
 *   1. Embeds _ogKA (basename.toLowerCase() → {id, m}) — used by MutationObserver
 *   2. Installs a MutationObserver that intercepts dynamically-added <link>/<script>
 *      whose basename is a known encrypted asset, removes the src/href, attaches
 *      data-og-asset-id so _ogUnlockEncryptedAssets can decrypt it later.
 *
 * This handles the common case where third-party JS (e.g. intl-tel-input) adds
 * stylesheet links programmatically — those never appear as HTML attributes and
 * are therefore not caught by og_patch_apply_asset_ids().
 */
function og_patch_dyn_asset_observer_snippet(array $assetMap): string
{
    if (empty($assetMap)) {
        return '';
    }

    // Build a lookup map keyed by BOTH the lowercase relPath AND the lowercase basename.
    // relPath key (e.g. "assets/css/main.css") wins on full-path matches (no collisions).
    // basename key (e.g. "main.css") is a best-effort fallback for dynamic JS refs that
    // use just a filename.  When two files share a basename the basename entry is set to
    // the FIRST file that claims it (first-writer-wins, not last), flagged ambiguous so
    // the JS can skip the basename lookup and avoid serving wrong content.
    $kaEntries = [];
    $bnSeen    = [];   // tracks which basenames have been claimed
    foreach ($assetMap as $relPath => $fileId) {
        $rl   = strtolower($relPath);
        $bn   = strtolower(basename($relPath));
        $mime = og_patch_asset_mime_type($relPath);
        // Always store the full relPath entry — it's unique.
        $kaEntries[$rl] = ['id' => $fileId, 'm' => $mime];
        if (!isset($bnSeen[$bn])) {
            // First file with this basename: store basename entry.
            $kaEntries[$bn] = ['id' => $fileId, 'm' => $mime];
            $bnSeen[$bn] = $rl;
        } elseif ($bnSeen[$bn] !== $rl) {
            // Collision: mark the basename entry ambiguous so JS skips it.
            $kaEntries[$bn] = ['id' => '', 'm' => $mime, 'ambiguous' => true];
        }
    }
    if (empty($kaEntries)) {
        return '';
    }

    $kaJson = json_encode($kaEntries, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    // Proactive DOM hooks: intercept link/script/img/source/iframe BEFORE the
    // browser fires the network request. MutationObserver alone is too late —
    // <link rel=stylesheet> kicks off fetch synchronously on insertion, so the
    // 404 ships before observer can re-route. We hook:
    //   1. Element.prototype.setAttribute
    //   2. href/src property setters on HTMLLinkElement/HTMLScriptElement/HTMLImageElement
    //   3. Node.prototype.appendChild / insertBefore (final safety net)
    //   4. MutationObserver (catches anything that slipped through, e.g. cloneNode)
    $js = <<<'DYNJS'
(function(){
var _ogKA=__OG_KA_JSON__;
var _ogDANew=false;
function _ogNormUrl(u){
  u=String(u||'');
  if(!u||/^(data:|blob:|javascript:|about:|#)/i.test(u))return '';
  return u.replace(/[?#].*/,'');
}
function _ogTagAttr(tag){
  tag=String(tag||'').toUpperCase();
  if(tag==='LINK')return 'href';
  if(tag==='SCRIPT'||tag==='IMG'||tag==='SOURCE'||tag==='IFRAME'||tag==='AUDIO'||tag==='VIDEO'||tag==='TRACK'||tag==='EMBED')return 'src';
  return null;
}
function _ogLookup(url){
  // Try full relPath first (no collision risk), then basename fallback.
  var p=_ogNormUrl(url);
  if(!p)return null;
  var rel=p.replace(/^\/+/,'').toLowerCase();
  var bn=rel.split('/').pop();
  var info=_ogKA[rel]||(_ogKA[bn]&&!_ogKA[bn].ambiguous?_ogKA[bn]:null);
  return (info&&info.id)?info:null;
}
function _ogTryRedirect(el,url){
  if(!el||!el.tagName)return false;
  if(el.getAttribute&&el.getAttribute('data-og-asset-id'))return false;
  var info=_ogLookup(url);
  if(!info)return false;
  var tag=String(el.tagName).toUpperCase();
  if(tag==='LINK'){
    var rel=String((el.getAttribute&&el.getAttribute('rel'))||'').toLowerCase();
    if(rel&&rel.indexOf('stylesheet')<0&&rel.indexOf('preload')<0&&rel.indexOf('modulepreload')<0&&rel.indexOf('icon')<0)return false;
  }
  try{
    var a=_ogTagAttr(tag);
    if(a&&el.removeAttribute)el.removeAttribute(a);
    el.setAttribute('data-og-asset-id',info.id);
    el.setAttribute('data-og-mime',info.m);
    _ogDANew=true;
    if(typeof window._ogFlushDynAssets==='function'){try{window._ogFlushDynAssets();}catch(xe){}}
    return true;
  }catch(e){return false;}
}
function _ogDAProc(el){
  if(!el||!el.tagName)return;
  var a=_ogTagAttr(el.tagName);
  if(!a)return;
  if(el.getAttribute('data-og-asset-id'))return;
  var url=el.getAttribute(a)||'';
  if(url)_ogTryRedirect(el,url);
}
// (1) Hook setAttribute on Element prototype — earliest interception point.
try{
  var _ogSetAttr=Element.prototype.setAttribute;
  Element.prototype.setAttribute=function(name,value){
    try{
      var ln=String(name||'').toLowerCase();
      if(ln==='href'||ln==='src'){
        var a=_ogTagAttr(this.tagName);
        if(a===ln&&_ogTryRedirect(this,String(value||'')))return;
      }
    }catch(e){}
    return _ogSetAttr.call(this,name,value);
  };
}catch(e){}
// (2) Hook href/src property setters on the elements that load network resources.
function _ogHookProp(proto,prop){
  if(!proto||!proto.hasOwnProperty)return;
  var d=Object.getOwnPropertyDescriptor(proto,prop);
  if(!d||!d.set||!d.configurable)return;
  var origSet=d.set,origGet=d.get;
  try{
    Object.defineProperty(proto,prop,{
      configurable:true,enumerable:d.enumerable,
      get:function(){return origGet?origGet.call(this):this.getAttribute(prop);},
      set:function(v){
        try{
          var a=_ogTagAttr(this.tagName);
          if(a===prop&&_ogTryRedirect(this,String(v||'')))return;
        }catch(e){}
        return origSet.call(this,v);
      }
    });
  }catch(e){}
}
try{_ogHookProp(HTMLLinkElement.prototype,'href');}catch(e){}
try{_ogHookProp(HTMLScriptElement.prototype,'src');}catch(e){}
try{_ogHookProp(HTMLImageElement.prototype,'src');}catch(e){}
try{if(window.HTMLIFrameElement)_ogHookProp(HTMLIFrameElement.prototype,'src');}catch(e){}
try{if(window.HTMLSourceElement)_ogHookProp(HTMLSourceElement.prototype,'src');}catch(e){}
try{if(window.HTMLMediaElement)_ogHookProp(HTMLMediaElement.prototype,'src');}catch(e){}
// (3) appendChild / insertBefore safety net — catches direct property assignment
// that bypassed setters (e.g. document.write, innerHTML), processing the node
// once it's about to enter the live DOM.
function _ogHookInsert(proto,name){
  if(!proto||!proto[name])return;
  var orig=proto[name];
  proto[name]=function(newNode,ref){
    try{
      if(newNode&&newNode.nodeType===1){
        _ogDAProc(newNode);
        if(newNode.querySelectorAll){
          var sel='link[href],script[src],img[src],iframe[src],source[src],audio[src],video[src]';
          newNode.querySelectorAll(sel).forEach(_ogDAProc);
        }
      }
    }catch(e){}
    return orig.call(this,newNode,ref);
  };
}
try{_ogHookInsert(Node.prototype,'appendChild');}catch(e){}
try{_ogHookInsert(Node.prototype,'insertBefore');}catch(e){}
// (4) MutationObserver final safety net (e.g. for cloneNode, innerHTML).
function _ogDAScan(root){try{(root&&root.querySelectorAll?root:document).querySelectorAll('link[href],script[src],img[src],iframe[src],source[src],audio[src],video[src]').forEach(_ogDAProc);}catch(e){}}
if(typeof MutationObserver!=='undefined'){
  try{
    new MutationObserver(function(ms){
      ms.forEach(function(m){
        m.addedNodes.forEach(function(n){
          if(n.nodeType===1){_ogDAProc(n);if(n.querySelectorAll)n.querySelectorAll('link[href],script[src],img[src],iframe[src],source[src],audio[src],video[src]').forEach(_ogDAProc);}
        });
      });
      if(_ogDANew&&typeof window._ogFlushDynAssets==='function'){try{window._ogFlushDynAssets();}catch(e){}}_ogDANew=false;
    }).observe(document.documentElement,{childList:true,subtree:true});
  }catch(e){}
}
// Scan elements already present at script-run time.
_ogDAScan(document.head);
_ogDAScan(document.documentElement);
})();
DYNJS;

    $js = str_replace('__OG_KA_JSON__', $kaJson, $js);
    // Collapse to single line
    $js = preg_replace('/\n\s*/', '', $js);

    return '<script id="og-dyn-asset-observer">' . $js . '</script>';
}

function og_patch_scan_offer_tree(string $offerPath, string $ogAssetsSubdir = '_og_assets'): array
{
    $meta = [
        'files_seen'    => 0,
        'truncated'     => false,
        'html_like'     => 0,
        'php_html'      => 0,
        'max_depth'     => og_patch_walk_max_depth(),
    ];
    $entries = [];
    $maxWalkFiles = 14000;
    $skipExtra = array_values(array_unique([$ogAssetsSubdir, '_og_assets']));
    try {
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($offerPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        $iter->setMaxDepth(og_patch_walk_max_depth());
        foreach ($iter as $f) {
            if (!$f->isFile()) {
                continue;
            }
            if ($meta['files_seen'] >= $maxWalkFiles) {
                $meta['truncated'] = true;

                break;
            }
            $meta['files_seen']++;
            $p = $f->getPathname();
            if (og_patch_should_skip_walk_path($p)) {
                continue;
            }
            foreach ($skipExtra as $s) {
                if ($s !== '' && (str_contains($p, "/$s/") || str_ends_with($p, "/$s"))) {
                    continue 2;
                }
            }
            $slug = og_patch_collect_template_ext_slug($p);
            $score = 0;
            $flags = [];
            if (in_array($slug, ['html', 'htm'], true)) {
                $score = 10;
                $flags[] = 'html';
                $meta['html_like']++;
            } else {
                $peek = og_patch_peek_file($p);
                if ($slug === 'php' || $slug === 'blade.php' || in_array($slug, ['phtml', 'php5', 'inc'], true)) {
                    if (og_patch_php_contains_html_output($peek) || og_patch_is_html_like_file($p, $peek)) {
                        if (og_patch_is_wp_theme_php_eligible($p)) {
                            $score = 8;
                            $flags[] = 'php-html';
                            $meta['php_html']++;
                        }
                    }
                } elseif (og_patch_is_html_like_file($p, $peek)) {
                    $score = 6;
                    $flags[] = 'template-html';
                    $meta['html_like']++;
                }
            }
            if ($score > 0) {
                $entries[] = ['path' => $p, 'score' => $score, 'flags' => $flags, 'slug' => $slug];
            }
        }
    } catch (Throwable $e) {
    }

    return [$entries, $meta];
}


function og_patch_is_html_like_file(string $path, string $content): bool
{
    $slug = og_patch_collect_template_ext_slug($path);
    if ($slug === 'html' || $slug === 'htm') {
        return true;
    }
    if ($content === '') {
        return false;
    }
    if (in_array($slug, ['php', 'phtml', 'php5', 'inc', 'blade.php'], true)) {
        return og_patch_php_contains_html_output($content) || (bool)preg_match('/<\!DOCTYPE\b|<\s*html\b|<\s*body\b/i', $content);
    }
    if ($slug === 'erb') {
        return (bool)preg_match('/<\!DOCTYPE\b|<\s*html\b|<\s*body\b/i', $content);
    }
    if (preg_match('/xmlns:th=|\bth:(insert|replace|fragment)\b|\blayout:decorate\b/i', $content)) {
        return (bool)preg_match('/<\s*[a-z!]/', $content);
    }
    if (og_patch_html_name_hint($path)
        && preg_match('/<(main|section|article|header|footer|nav|div)\b/i', $content)) {
        return true;
    }

    return (bool)preg_match('/<\!DOCTYPE\b|<\s*html\b|<\s*body\b/i', $content);
}


function og_patch_collect_files(string $offerPath, string $ogAssetsSubdir = '_og_assets'): array
{
    $htmlFiles = [];
    $phpFiles = [];
    $phpHtmlFiles = [];
    $templateExtCounts = [];
    $meta = [
        'template_ext_counts' => &$templateExtCounts,
        'walk_files_seen'     => 0,
        'walk_truncated'      => false,
        'html_truncated'      => false,
        'php_html_count'      => 0,
        'scan_deep'           => !empty($GLOBALS['OG_PATCH_SCAN_DEEP']),
        'skipped_dir_rule'    => 0,
        'skipped_depth'       => 0,
        'skipped_cap'         => 0,
        'candidate_ext'       => 0,
        'candidate_sniff'     => 0,
        'selected_html'       => 0,
        'selected_php_html'   => 0,
        'skipped_dirs_top'    => [],
    ];
    $limits = og_patch_scan_limits();
    $maxWalkFiles = (int)$limits['max_walk_files'];
    $maxHtmlPatch = (int)$limits['max_html_patch'];
    $maxDepth = og_patch_walk_max_depth();
    $normPath = static fn(string $x): string => strtolower(str_replace('\\', '/', $x));
    $addHtmlIfRoom = static function (string $p) use (&$htmlFiles, &$meta, $maxHtmlPatch): void {
        if (count($htmlFiles) >= $maxHtmlPatch) {
            $meta['html_truncated'] = true;
            $meta['skipped_cap']++;

            return;
        }
        $htmlFiles[] = $p;
        $meta['selected_html']++;
    };
    $templateSlugs = [
        'twig', 'jinja2', 'jsp', 'jspf', 'asp', 'aspx', 'cshtml', 'erb',
        'ejs', 'hbs', 'mustache', 'vue', 'tsx', 'jsx',
    ];
    $skipExtra = array_values(array_unique([$ogAssetsSubdir, '_og_assets']));
    $trackSkipDir = static function (string $pathname) use (&$meta): void {
        $pn = strtolower(str_replace('\\', '/', $pathname));
        foreach (og_patch_walk_skip_segments() as $seg) {
            if (str_contains($pn, '/' . $seg . '/') || str_ends_with($pn, '/' . $seg)) {
                $meta['skipped_dirs_top'][$seg] = ($meta['skipped_dirs_top'][$seg] ?? 0) + 1;
                return;
            }
        }
        if (str_contains($pn, '/.next/cache/')) {
            $meta['skipped_dirs_top']['.next/cache'] = ($meta['skipped_dirs_top']['.next/cache'] ?? 0) + 1;
        } elseif (str_contains($pn, '/.nuxt/dist/')) {
            $meta['skipped_dirs_top']['.nuxt/dist'] = ($meta['skipped_dirs_top']['.nuxt/dist'] ?? 0) + 1;
        }
    };
    try {
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($offerPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        $iter->setMaxDepth($maxDepth + 2);
        foreach ($iter as $f) {
            $p = $f->getPathname();
            $rel = str_replace('\\', '/', ltrim(str_replace(rtrim(str_replace('\\', '/', $offerPath), '/') . '/', '', str_replace('\\', '/', $p)), '/'));
            $depth = $rel === '' ? 0 : substr_count($rel, '/');
            if ($depth > $maxDepth) {
                $meta['skipped_depth']++;
                continue;
            }
            if ($meta['walk_files_seen'] >= $maxWalkFiles) {
                $meta['walk_truncated'] = true;
                $meta['skipped_cap']++;

                break;
            }
            if (!$f->isFile()) {
                continue;
            }
            $meta['walk_files_seen']++;
            if (og_patch_should_skip_walk_path($p)) {
                $meta['skipped_dir_rule']++;
                $trackSkipDir($p);
                continue;
            }
            foreach ($skipExtra as $s) {
                if ($s !== '' && (str_contains($p, "/$s/") || str_ends_with($p, "/$s"))) {
                    $meta['skipped_dir_rule']++;
                    $meta['skipped_dirs_top'][$s] = ($meta['skipped_dirs_top'][$s] ?? 0) + 1;
                    continue 2;
                }
            }
            $slug = og_patch_collect_template_ext_slug($p);
            $ext = strtolower($f->getExtension());
            $pn = $normPath($p);
            if (in_array($slug, ['html', 'htm', 'php', 'phtml', 'php5', 'inc', 'blade.php'], true)
                || in_array($slug, $templateSlugs, true)) {
                $meta['candidate_ext']++;
            }
            if ($slug !== '' && !in_array($slug, ['html', 'htm', 'php', 'phtml', 'php5', 'inc', 'blade.php'], true)) {
                $templateExtCounts[$slug] = ($templateExtCounts[$slug] ?? 0) + 1;
            }
            $pyTemplateDir = in_array(basename(dirname($p)), ['templates', 'template', 'jinja2', 'views', 'pages', 'layouts', 'components'], true)
                || preg_match('#/(templates|views|pages|layouts|components)(/|$)#i', $pn) === 1;
            if (($ext === 'py' || $slug === 'py') && !$pyTemplateDir) {
                continue;
            }
            if (in_array($slug, ['php', 'blade.php'], true)) {
                if ($slug !== 'blade.php') {
                    $phpFiles[] = $p;
                }
            } elseif ($ext === 'phtml' || $ext === 'php5' || $ext === 'inc') {
                $phpFiles[] = $p;
            }
            if (in_array($slug, ['html', 'htm'], true)) {
                $addHtmlIfRoom($p);

                continue;
            }
            $skipHtmlPhp = strtolower(basename($p)) === 'functions.php';
            if ($slug === 'blade.php') {
                $peek = og_patch_peek_file($p);
                if (og_patch_is_html_like_file($p, $peek)) {
                    $addHtmlIfRoom($p);
                }

                continue;
            }
            if (in_array($slug, ['php', 'phtml', 'php5', 'inc'], true) && !$skipHtmlPhp) {
                $peek = og_patch_peek_file($p);
                $phpHtml = og_patch_php_contains_html_output($peek);
                $htmlLike = og_patch_is_html_like_file($p, $peek);
                if ($phpHtml || $htmlLike) {
                    $meta['candidate_sniff']++;
                    if (og_patch_is_wp_theme_php_eligible($p)) {
                        $addHtmlIfRoom($p);
                        if ($phpHtml) {
                            $phpHtmlFiles[] = $p;
                            $meta['php_html_count']++;
                            $meta['selected_php_html']++;
                        }
                    }
                }

                continue;
            }
            if (in_array($slug, $templateSlugs, true)) {
                $peek = og_patch_peek_file($p);
                if (og_patch_is_html_like_file($p, $peek)) {
                    $meta['candidate_sniff']++;
                    $addHtmlIfRoom($p);
                }

                continue;
            }
            if (($ext === 'py' || $slug === 'py') && $pyTemplateDir) {
                $peek = og_patch_peek_file($p);
                if (stripos($peek, '{#') !== false && !preg_match('/<\!DOCTYPE\b|<\s*html\b|<\s*body\b/i', $peek)) {

                } elseif (og_patch_is_html_like_file($p, $peek)) {
                    $meta['candidate_sniff']++;
                    $addHtmlIfRoom($p);
                }
            }
        }
    } catch (Throwable $e) {
    }
    if (!empty($meta['skipped_dirs_top']) && is_array($meta['skipped_dirs_top'])) {
        arsort($meta['skipped_dirs_top']);
        $meta['skipped_dirs_top'] = array_slice($meta['skipped_dirs_top'], 0, 6, true);
    }
    if ($meta['php_html_count'] > 0 || count($htmlFiles) > 0) {
        info('[OfferGuard] scan: found ' . count($htmlFiles) . ' html-like, '
            . (int)$meta['php_html_count'] . ' php-html in ' . $offerPath);
    }
    info('[OfferGuard] scan report: files=' . (int)$meta['walk_files_seen']
        . ' skipped(dir)=' . (int)$meta['skipped_dir_rule']
        . ' skipped(depth/cap)=' . ((int)$meta['skipped_depth'] + (int)$meta['skipped_cap']
        . ' [depth=' . (int)$meta['skipped_depth'] . ', cap=' . (int)$meta['skipped_cap'] . ']')
        . ' candidates(ext)=' . (int)$meta['candidate_ext']
        . ' candidates(sniff)=' . (int)$meta['candidate_sniff']
        . ' selected(html/php)=' . count($htmlFiles) . '/' . (int)$meta['selected_php_html']
        . (!empty($meta['scan_deep']) ? ' mode=deep' : ' mode=default'));
    if (!empty($meta['skipped_dirs_top']) && is_array($meta['skipped_dirs_top'])) {
        $parts = [];
        foreach ($meta['skipped_dirs_top'] as $k => $v) {
            $parts[] = $k . ':' . (int)$v;
        }
        info('[OfferGuard] scan report: top skipped dirs => ' . implode(', ', $parts));
    }
    if ($meta['walk_truncated']) {
        warn('[OfferGuard] collect: ліміт обходу файлів скинутий — патч охоплює лише частину оффера');
    }
    if ($meta['html_truncated']) {
        warn('[OfferGuard] collect: ліміт кількості HTML-like файлів скинутий');
    }

    $htmlFiles = array_values(array_unique($htmlFiles));
    $phpHtmlFiles = array_values(array_unique($phpHtmlFiles));
    sort($htmlFiles);
    sort($phpFiles);
    $meta['php_html_files'] = $phpHtmlFiles;

    return [$htmlFiles, $phpFiles, $meta];
}

function og_patch_is_local_dev_host(string $host): bool
{
    $h = og_patch_normalize_host($host);
    if ($h === '') {
        return false;
    }
    if (in_array($h, ['localhost', '127.0.0.1', '0.0.0.0', '::1'], true)) {
        return true;
    }

    return (bool)preg_match('/\.(local|localhost|test|invalid)$/', $h);
}


function og_patch_is_cdn_host(string $host): bool
{
    $h = og_patch_normalize_host($host);
    if ($h === '') {
        return true;
    }
    $cdnNeedles = [
        'cdn.', 'cdnjs.', 'cloudfront.net', 'akamaized.net', 'akamai', 'fastly.net',
        'jsdelivr.net', 'unpkg.com', 'gstatic.com', 'googleapis.com', 'bootstrapcdn.com',
        'cloudflare.com', 'cloudflareinsights.com', 'staticfile.org', 'imgix.net',
        'stackpathcdn.com', 'kxcdn.com', 'bunnycdn.', 'digitaloceanspaces.com',
    ];
    foreach ($cdnNeedles as $needle) {
        if (str_contains($h, $needle)) {
            return true;
        }
    }

    return (bool)preg_match('/^cdn[\d-]*\./', $h);
}


function og_patch_collect_hosts_from_html(string $src): array
{
    $counts = [];
    $add = static function (string $host) use (&$counts): void {
        $h = og_patch_normalize_host($host);
        if ($h === '' || !preg_match('/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$/i', $h)) {
            return;
        }
        $counts[$h] = ($counts[$h] ?? 0) + 1;
    };
    $addUrl = static function (string $url) use ($add): void {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($url === '' || !preg_match('#^https?://#i', $url)) {
            return;
        }
        $h = parse_url($url, PHP_URL_HOST);
        if (is_string($h) && $h !== '') {
            $add($h);
        }
    };

    if (preg_match_all('#\bhttps?://([a-z0-9](?:[a-z0-9.-]*[a-z0-9])?)#i', $src, $abs)) {
        foreach ($abs[1] as $h) {
            $add((string)$h);
        }
    }
    if (preg_match_all('/<link\b[^>]*\bhref\s*=\s*(["\'])(.*?)\1/i', $src, $lm, PREG_SET_ORDER)) {
        foreach ($lm as $m) {
            $addUrl((string)($m[2] ?? ''));
        }
    }
    if (preg_match_all('/<form\b[^>]*\baction\s*=\s*(["\'])(.*?)\1/i', $src, $fm, PREG_SET_ORDER)) {
        foreach ($fm as $m) {
            $addUrl((string)($m[2] ?? ''));
        }
    }
    if (preg_match_all('/<meta\b[^>]*\bproperty\s*=\s*(["\'])og:url\1[^>]*\bcontent\s*=\s*\1([^\1>]+)\1/i', $src, $og1, PREG_SET_ORDER)) {
        foreach ($og1 as $m) {
            $addUrl((string)($m[2] ?? ''));
        }
    }
    if (preg_match_all('/<meta\b[^>]*\bcontent\s*=\s*(["\'])([^\1>]+)\1[^>]*\bproperty\s*=\s*\1og:url\1/i', $src, $og2, PREG_SET_ORDER)) {
        foreach ($og2 as $m) {
            $addUrl((string)($m[2] ?? ''));
        }
    }

    return $counts;
}

function og_patch_read_og_origin_meta(string $src): string
{
    if (preg_match('/<meta\b[^>]*\bname\s*=\s*([\'"])og-origin-host\1[^>]*\bcontent\s*=\s*\1([^\1>]+)\1/i', $src, $m)) {
        return og_patch_normalize_host(html_entity_decode((string)($m[2] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
    if (preg_match('/<meta\b[^>]*\bcontent\s*=\s*"([^"]*)"[^>]*\bname\s*=\s*"og-origin-host"/i', $src, $m2)) {
        return og_patch_normalize_host(html_entity_decode((string)($m2[1] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
    if (preg_match('/<meta\b[^>]*\bcontent\s*=\s*\'([^\']*)\'[^>]*\bname\s*=\s*\'og-origin-host\'/i', $src, $m3)) {
        return og_patch_normalize_host(html_entity_decode((string)($m3[1] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    return '';
}


function og_patch_read_og_canonical_comment(string $src): string
{
    if (preg_match('/<!--\s*og:canonical=([a-z0-9](?:[a-z0-9.-]*[a-z0-9])?)\s*-->/i', $src, $m)) {
        return og_patch_normalize_host((string)($m[1] ?? ''));
    }

    return '';
}


function og_patch_read_canonical_from_bot_protect(string $offerRoot): string
{
    $path = rtrim($offerRoot, '/') . '/bot-protect.php';
    if (!is_file($path)) {
        return '';
    }
    $src = (string)@file_get_contents($path);
    if ($src === '' || !preg_match("/'canonical_host'\\s*=>\\s*'([^']*)'/", $src, $m)) {
        return '';
    }
    $h = og_patch_normalize_host((string)($m[1] ?? ''));
    if ($h === '' || $h === 'og_canonical_host_change_me') {
        return '';
    }

    return og_patch_host_looks_like_domain($h) ? $h : '';
}


function og_patch_collapse_www_host_counts(array $counts): array
{
    $merged = [];
    foreach ($counts as $h => $w) {
        $key = og_patch_host_apex($h);
        if ($key === '') {
            continue;
        }
        $merged[$key] = ($merged[$key] ?? 0) + (int)$w;
    }

    return $merged;
}


function og_patch_pick_canonical_from_counts(array $counts): array
{
    if ($counts === []) {
        return ['', false, []];
    }
    $counts = og_patch_collapse_www_host_counts($counts);
    if ($counts === []) {
        return ['', false, []];
    }
    $local = [];
    $nonLocal = [];
    foreach ($counts as $h => $w) {
        if (og_patch_is_local_dev_host($h)) {
            $local[$h] = $w;
        } else {
            $nonLocal[$h] = $w;
        }
    }
    $pool = $nonLocal !== [] ? $nonLocal : $local;
    if ($pool === []) {
        return ['', false, []];
    }
    $cdn = [];
    $origin = [];
    foreach ($pool as $h => $w) {
        if (og_patch_is_cdn_host($h)) {
            $cdn[$h] = $w;
        } else {
            $origin[$h] = $w;
        }
    }
    $pickPool = $origin !== [] ? $origin : $cdn;
    arsort($pickPool, SORT_NUMERIC);
    $candidates = array_keys($pickPool);
    $top = $candidates[0] ?? '';
    if ($top === '') {
        return ['', false, []];
    }
    $topScore = $pickPool[$top];
    $ties = [];
    foreach ($pickPool as $h => $w) {
        if ($w === $topScore) {
            $ties[] = $h;
        }
    }
    $ambiguous = count($ties) > 1;

    return [$top, $ambiguous, $ties];
}


function og_patch_detect_canonical_from_html(array $htmlFiles): array
{
    $ordered = $htmlFiles;
    usort($ordered, static function (string $a, string $b): int {
        $pri = static function (string $p): int {
            $base = strtolower(basename($p));
            if ($base === 'index.html' || $base === 'index.htm') {
                return 0;
            }
            if ($base === 'main.html' || $base === 'landing.html') {
                return 1;
            }

            return 2;
        };
        $pa = $pri($a);
        $pb = $pri($b);
        if ($pa !== $pb) {
            return $pa <=> $pb;
        }

        return strcmp($a, $b);
    });

    $merged = [];
    foreach ($ordered as $p) {
        $src = (string)@file_get_contents($p);
        if ($src === '') {
            continue;
        }
        $meta = og_patch_read_og_origin_meta($src);
        if ($meta !== '' && $meta !== 'og_canonical_host_change_me') {
            return ['host' => $meta, 'ambiguous' => false, 'candidates' => [$meta], 'source' => 'meta'];
        }
        foreach (og_patch_collect_hosts_from_html($src) as $h => $w) {
            $merged[$h] = ($merged[$h] ?? 0) + $w;
        }
    }
    [$host, $ambiguous, $candidates] = og_patch_pick_canonical_from_counts($merged);
    if ($host === '') {
        return ['host' => '', 'ambiguous' => false, 'candidates' => [], 'source' => ''];
    }

    return ['host' => $host, 'ambiguous' => $ambiguous, 'candidates' => $candidates, 'source' => 'html'];
}


function og_patch_host_looks_like_domain(string $host): bool
{
    $h = og_patch_normalize_host($host);
    if ($h === '' || $h === 'localhost') {
        return false;
    }
    if (filter_var($h, FILTER_VALIDATE_IP)) {
        return false;
    }
    if (og_patch_is_local_dev_host($h)) {
        return false;
    }
    if (preg_match('/^[a-z0-9]([a-z0-9-]*\.)+[a-z]{2,}$/i', $h)) {
        return true;
    }
    if (str_contains($h, '.') && preg_match('/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$/i', $h)) {
        return true;
    }

    return false;
}

function og_patch_is_generic_web_dir(string $name): bool
{
    return in_array(strtolower(trim($name)), ['public_html', 'www', 'htdocs', 'public', 'html', 'web'], true);
}


function og_patch_cli_path_is_explicit(string $argvPath): bool
{
    $raw = trim($argvPath);
    if ($raw === '' || $raw === '.' || $raw === './') {
        return false;
    }
    $norm = str_replace('\\', '/', $raw);
    foreach (['public_html', 'www', 'htdocs', 'out', 'dist', 'templates'] as $sub) {
        if (preg_match('#(?:^|/)' . preg_quote($sub, '#') . '(?:/|$)#i', $norm)) {
            return true;
        }
    }
    $resolved = @realpath($raw);
    if ($resolved !== false && $resolved !== '') {
        if (og_patch_is_generic_web_dir(basename($resolved))) {
            return true;
        }
        $parent = dirname($resolved);
        if ($parent !== $resolved && og_patch_host_looks_like_domain(basename($parent))) {
            return true;
        }
    }
    $base = basename(rtrim($norm, '/'));
    if ($base !== '' && $base !== '.' && !og_patch_host_looks_like_domain($base)) {
        return true;
    }

    return false;
}


function og_patch_parse_rollback_from_args(array $args): array
{
    $rollback = false;
    $typoNote = null;
    foreach ($args as $a) {
        if (!is_string($a) || !str_starts_with($a, '--')) {
            continue;
        }
        if ($a === '--rollback') {
            $rollback = true;
            continue;
        }
        if (preg_match('/^--rollback/i', $a)) {
            $rollback = true;
            $typoNote = 'Прапор `' . $a . '` — ймовірно мали на увазі `--rollback`. Відкат увімкнено.';
        }
    }

    return ['rollback' => $rollback, 'typo_note' => $typoNote];
}


function og_patch_cli_is_maintenance_mode(array $args): bool
{
    static $flags = [
        '--rollback', '--status', '--verify-copy', '--cleanup',
        '--bans', '--whitelist', '--traffic', '--sessions', '--tail',
        '--og-emergency-unbreak',
    ];
    foreach ($flags as $f) {
        if (in_array($f, $args, true)) {
            return true;
        }
    }
    foreach ($args as $a) {
        if (!is_string($a)) {
            continue;
        }
        if (preg_match('/^--rollback/i', $a)) {
            return true;
        }
        if ($a === '--unban' || $a === '--allow' || $a === '--deny') {
            return true;
        }
    }

    return false;
}


function og_patch_cli_should_skip_auto_promote(array $args): bool
{
    return og_patch_cli_is_maintenance_mode($args);
}


function og_patch_path_under_root(string $path, string $root): bool
{
    $path = rtrim(str_replace('\\', '/', realpath($path) ?: $path), '/');
    $root = rtrim(str_replace('\\', '/', realpath($root) ?: $root), '/');
    if ($path === $root) {
        return true;
    }

    return str_starts_with($path, $root . '/');
}


function og_patch_file_has_offer_guard_traces(string $content): bool
{
    if ($content === '') {
        return false;
    }
    // v6 + v5 markers
    if (str_contains($content, '[OfferGuard:') || str_contains($content, '[OfferGuard:start]')) {
        return true;
    }
    if (str_contains($content, 'data-og-enc-') || str_contains($content, 'og-origin-host')) {
        return true;
    }
    if (preg_match('/\/\*\s*\[OfferGuard:start\]\s*\*\//', $content)) {
        return true;
    }
    // OG v4 legacy markers — `# OfferGuard v4` у .htaccess, `OG_V6`/`og_protect` у PHP,
    // `data-og-canonical-lock`/`og-expected-host` у HTML.
    if (str_contains($content, '# OfferGuard v4')
        || str_contains($content, '# OfferGuard v3')
        || str_contains($content, 'OG_V6')
        || str_contains($content, 'og_protect_start')
        || str_contains($content, 'data-og-canonical-lock')
        || str_contains($content, 'og-expected-host')) {
        return true;
    }

    return false;
}


function og_patch_is_bot_protect_file(string $path): bool
{
    if (!is_file($path) || basename($path) !== 'bot-protect.php') {
        return false;
    }
    $src = (string)@file_get_contents($path);

    return str_contains($src, 'OfferGuard') && str_contains($src, 'og_webhook_mode');
}


function og_patch_meta_was_patched(array $meta, string $origPath, string $bkPath): bool
{
    if (!empty($meta['og_patched']) || !empty($meta['og_version'])) {
        return true;
    }
    if (!is_file($bkPath)) {
        return false;
    }
    $bk = (string)@file_get_contents($bkPath);
    $cur = is_file($origPath) ? (string)@file_get_contents($origPath) : '';
    if ($cur === '' || $bk === $cur) {
        return false;
    }

    return og_patch_file_has_offer_guard_traces($cur);
}


function og_patch_load_backup_meta(string $backupDir): array
{
    $items = [];
    foreach (glob(rtrim($backupDir, '/') . '/*.meta') ?: [] as $metaFile) {
        $meta = json_decode((string)@file_get_contents($metaFile), true) ?: [];
        $orig = (string)($meta['original'] ?? '');
        $bk = substr($metaFile, 0, -5);
        if ($orig === '' || !is_file($bk)) {
            continue;
        }
        $items[] = [
            'meta' => $meta,
            'original' => $orig,
            'backup' => $bk,
            'meta_file' => $metaFile,
        ];
    }

    return $items;
}


function og_patch_strip_htaccess_offer_guard(string $content): string
{
    // v6 markers
    $stripped = preg_replace(
        '/# \[OfferGuard:universal-runtime\][\s\S]*?# \[\/OfferGuard:universal-runtime\]\s*/',
        '',
        $content
    ) ?? $content;
    $stripped = preg_replace(
        '/# \[OfferGuard:emergency-safe\][\s\S]*?# \[\/OfferGuard:emergency-safe\]\s*/',
        '',
        $stripped
    ) ?? $stripped;
    $stripped = preg_replace(
        '/# OfferGuard v6[\s\S]*?(?=\n# (?!\[OfferGuard)|\z)/',
        '',
        $stripped,
        1
    ) ?? $stripped;
    // OG v4 markers (legacy installations) — старий формат без [..:..] тегів.
    // Стрипаємо ТОЧКОВО:
    // - # OfferGuard v4 — Apache (safe profile)  (один рядок)
    // - <FilesMatch> для bot-protect.php (це блокувало heartbeat → 403)
    // - RewriteCond UA-блок (це 403'ило curl/wget — false positive на тестах)
    // - RewriteRule на _og_data/_og_backup/_og_trap
    // Залишаємо все що НЕ є OG v4.
    $stripped = preg_replace('/^# OfferGuard v4[^\n]*\n/m', '', $stripped) ?? $stripped;
    $stripped = preg_replace(
        '/<FilesMatch\s+"[^"]*bot-protect[^"]*"[^>]*>[\s\S]*?<\/FilesMatch>\s*\n?/',
        '',
        $stripped
    ) ?? $stripped;
    // Бан UA блок (точно OG v4: curl/wget/python-requests + httrack)
    $stripped = preg_replace(
        '/[ \t]*#\s*Блок\s+(?:явных\s+ботов|инструментов\s+выкачивания)[^\n]*\n[ \t]*RewriteCond[^\n]*(?:python-requests|curl\/|wget\/|httrack|teleport)[^\n]*\n[ \t]*RewriteRule\s+\^\s+-\s+\[F,L\]\s*\n?/i',
        '',
        $stripped
    ) ?? $stripped;
    // _og_data/_og_backup RewriteRules
    $stripped = preg_replace(
        '/[ \t]*#\s*Защита\s+служебных\s+папок[^\n]*\n[ \t]*RewriteRule\s+\^_og_data\/\s+-\s+\[F,L\]\s*\n[ \t]*RewriteRule\s+\^_og_backup\/\s+-\s+\[F,L\]\s*\n?/i',
        '',
        $stripped
    ) ?? $stripped;
    // _og_trap RewriteRules
    $stripped = preg_replace(
        '/[ \t]*#\s*Приманки[^\n]*\n[ \t]*RewriteRule\s+\^\([^)]*_og_trap[^)]*\)\$\s+-\s+\[F,L\]\s*\n?/i',
        '',
        $stripped
    ) ?? $stripped;

    return trim($stripped);
}


/**
 * Сканирует места, где могли осесть установки OfferGuard:
 * сам root, его parent, и subdirs до глубины 2. Возвращает массив записей
 * {root, bot_protect?, data_dir?, log_blocked?, log_runtime?}.
 * Нужен чтобы --status находил логи и инсталляции даже если patch root сместился
 * между запусками (например, было запатчено в parent, теперь — в public_html).
 */
function og_patch_status_collect_installs(string $root): array
{
    $root = rtrim(realpath($root) ?: $root, '/');
    $candidates = [$root];
    $parent = dirname($root);
    if ($parent !== '' && $parent !== $root && is_dir($parent)) {
        $candidates[] = $parent;
    }
    foreach (@glob($root . '/*', GLOB_ONLYDIR) ?: [] as $sub) {
        $name = basename($sub);
        if (str_starts_with($name, '.') || $name === '_og_backup' || $name === '_og_data'
            || $name === '_og_assets' || $name === 'node_modules' || $name === 'vendor') {
            continue;
        }
        if (og_patch_is_generic_web_dir($name) || is_file($sub . '/bot-protect.php')
            || is_dir($sub . '/_og_data')) {
            $candidates[] = $sub;
        }
    }
    $installs = [];
    $seen = [];
    foreach ($candidates as $c) {
        $abs = rtrim(realpath($c) ?: $c, '/');
        if (isset($seen[$abs])) continue;
        $seen[$abs] = true;
        $bp = $abs . '/bot-protect.php';
        $dd = $abs . '/_og_data';
        $lb = $dd . '/og_blocked.log';
        $lr = $dd . '/og_runtime.log';
        $hasBp = is_file($bp) && og_patch_is_bot_protect_file($bp);
        $hasDd = is_dir($dd);
        if (!$hasBp && !$hasDd) continue;
        $installs[] = [
            'root' => $abs,
            'bot_protect' => $hasBp ? $bp : null,
            'data_dir' => $hasDd ? $dd : null,
            'log_blocked' => is_file($lb) ? $lb : null,
            'log_runtime' => is_file($lr) ? $lr : null,
        ];
    }
    return $installs;
}

/**
 * Эвристика для распознавания legacy cloak-папок без `.og_cloak` маркера.
 * Cloak: ≤6 файлов, есть index.html со следами OfferGuard ИЛИ decoy "картинки"
 * с не-image заголовками (зашифрованный бандл маскируется под png/jpg).
 */
function og_patch_dir_looks_like_legacy_cloak(string $dir): bool
{
    if (!is_dir($dir)) return false;
    $files = @scandir($dir) ?: [];
    $real = array_values(array_filter($files, fn($f) => $f !== '.' && $f !== '..'));
    if (count($real) === 0 || count($real) > 8) return false;
    $hasOgIndex = false;
    $fakeImages = 0;
    foreach ($real as $f) {
        $p = $dir . '/' . $f;
        if (!is_file($p)) return false;
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if ($f === 'index.html' || $f === 'index.htm') {
            $head = (string)@file_get_contents($p, false, null, 0, 4096);
            if (str_contains($head, '[OfferGuard:') || str_contains($head, 'data-og-enc')
                || str_contains($head, 'og-bundle') || str_contains($head, 'OfferGuard')) {
                $hasOgIndex = true;
            }
        } elseif (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
            // Перевіряємо magic bytes: справжня картинка починається з відомих сигнатур.
            $magic = (string)@file_get_contents($p, false, null, 0, 8);
            $isReal = $magic !== '' && (
                str_starts_with($magic, "\x89PNG\r\n\x1a\n")
                || str_starts_with($magic, "\xff\xd8\xff")
                || str_starts_with($magic, 'GIF87a') || str_starts_with($magic, 'GIF89a')
                || str_starts_with($magic, 'RIFF')
            );
            if (!$isReal) $fakeImages++;
        }
    }
    return $hasOgIndex || $fakeImages >= 2;
}

function og_patch_find_offer_guard_traces(string $root): array
{
    $root = rtrim(realpath($root) ?: $root, '/');
    $traces = ['files' => [], 'dirs' => []];
    if (!is_dir($root)) {
        return $traces;
    }

    $addFile = static function (string $path, string $kind, array $labels) use (&$traces, $root): void {
        $key = realpath($path) ?: $path;
        if (isset($traces['files'][$key])) {
            return;
        }
        $rel = str_replace($root . '/', '', $path);
        if ($rel === $path) {
            $rel = basename($path);
        }
        $traces['files'][$key] = ['path' => $path, 'rel' => $rel, 'kind' => $kind, 'labels' => $labels];
    };

    // 1) System dirs у root + усе дерево (cloak'и можуть бути на будь-якій глибині)
    foreach (['_og_data', '_og_assets', '_og_backup'] as $d) {
        $dp = $root . '/' . $d;
        if (is_dir($dp)) {
            $traces['dirs'][$d] = $dp;
        }
    }
    // Рекурсивно шукаємо ВСІ cloak-папки і system-папки на будь-якій глибині —
    // не тільки в root. Бо повторні патчі можуть створити їх у вкладених webroot'ах.
    try {
        $dirIter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        $dirIter->setMaxDepth(4);
        foreach ($dirIter as $df) {
            if (!$df->isDir()) continue;
            $dp = $df->getPathname();
            $name = $df->getFilename();
            // skip recursing into backup (бекапи — не треба сканувати їх вміст)
            if (str_contains($dp, '/_og_backup/')) continue;
            if ($name === '_og_data' || $name === '_og_assets' || $name === '_og_backup') {
                $relKey = str_replace($root . '/', '', $dp);
                if (!isset($traces['dirs'][$relKey])) $traces['dirs'][$relKey] = $dp;
                continue;
            }
            // cloak з маркером .og_cloak — на будь-якій глибині
            if (is_file($dp . '/.og_cloak')) {
                $relKey = str_replace($root . '/', '', $dp);
                $traces['dirs'][$relKey] = $dp;
                continue;
            }
            // legacy cloak без маркера — heuristic (тільки top-level + 1, deeper too greedy)
            if (substr_count(str_replace($root, '', $dp), '/') <= 2
                && !str_starts_with($name, '.')
                && !og_patch_is_generic_web_dir($name)
                && og_patch_dir_looks_like_legacy_cloak($dp)) {
                $relKey = str_replace($root . '/', '', $dp);
                $traces['dirs'][$relKey] = $dp;
            }
        }
    } catch (Throwable $e) {}

    // 2) Out-of-tree .og_secret в parent
    $parent = dirname($root);
    if ($parent !== '' && $parent !== $root && is_dir($parent)) {
        $sec = $parent . '/.og_secret';
        if (is_file($sec)) {
            $addFile($sec, 'secret', ['out-of-tree secret']);
        }
    }

    // 3) bot-protect.php — у root АБО будь-якому subdir (повторні патчі)
    foreach (['', '/public_html', '/www', '/htdocs', '/public', '/web'] as $sub) {
        $bp = $root . $sub . '/bot-protect.php';
        if (og_patch_is_bot_protect_file($bp)) {
            $addFile($bp, 'bot-protect', ['bot-protect.php' . ($sub !== '' ? " in $sub" : '')]);
        }
    }

    // 4) .htaccess з OG блоком — у root + усіх webroot subdirs
    foreach (['', '/public_html', '/www', '/htdocs', '/public', '/web'] as $sub) {
        $ht = $root . $sub . '/.htaccess';
        if (is_file($ht)) {
            $htSrc = (string)@file_get_contents($ht);
            if (str_contains($htSrc, '[OfferGuard:') || str_contains($htSrc, 'OfferGuard v6')) {
                $addFile($ht, 'htaccess', ['OfferGuard htaccess block' . ($sub !== '' ? " in $sub" : '')]);
            }
        }
    }

    // 5) Рекурсивний скан ВСЬОГО дерева — HTML/PHP/etc з OG-маркерами
    $scanExt = ['php', 'html', 'htm', 'phtml', 'tpl', 'twig', 'blade.php', 'txt'];
    try {
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iter as $f) {
            if (!$f->isFile()) {
                continue;
            }
            $path = $f->getPathname();
            $base = $f->getFilename();
            if ($base === 'bot-protect.php' || (str_starts_with($base, '.') && $base !== '.htaccess')) {
                continue;
            }
            if (str_contains($path, '/_og_backup/') || str_contains($path, '/node_modules/')
                || str_contains($path, '/.git/')) {
                continue;
            }
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            // OG-related files за іменем — навіть якщо не html/php
            $ogByName = preg_match('/^(og_|\.og_)/', $base) === 1
                || $base === '.og_secret' || $base === '.og_cloak'
                || str_starts_with($base, 'og_runtime') || str_starts_with($base, 'og_blocked')
                || str_starts_with($base, 'og_traffic') || str_starts_with($base, 'og_sessions')
                || $base === 'og_framework_applied.json';
            if ($ogByName) {
                // Багато з них всередині _og_data — вже покриті dirs.
                // Якщо файл лежить НЕ в _og_data — це сирітка з минулого патчу.
                if (!str_contains($path, '/_og_data/') && !str_contains($path, '/_og_assets/')) {
                    $addFile($path, 'orphan', ['OG orphan file: ' . $base]);
                }
                continue;
            }
            if (!in_array($ext, $scanExt, true) && !str_ends_with(strtolower($path), '.blade.php')) {
                continue;
            }
            $src = (string)@file_get_contents($path);
            if ($src === '' || !og_patch_file_has_offer_guard_traces($src)) {
                continue;
            }
            $labels = [];
            if (str_contains($src, '[OfferGuard:start]') || str_contains($src, '/* [OfferGuard:start] */')) {
                $labels[] = 'PHP OfferGuard block';
            }
            if (str_contains($src, '<!-- [OfferGuard:')) {
                $labels[] = 'HTML OfferGuard inject';
            }
            if (str_contains($src, 'data-og-enc-')) {
                $labels[] = 'encrypted payload';
            }
            $kind = ($ext === 'php' || str_ends_with(strtolower($path), '.phtml')) ? 'php' : 'html';
            $addFile($path, $kind, $labels !== [] ? $labels : ['OfferGuard trace']);
        }
    } catch (Throwable $e) {
    }

    // 6) patch.php.save* у root і parent (від попередніх vim/sed)
    foreach ([$root, $parent] as $rd) {
        if ($rd === '' || !is_dir($rd)) continue;
        foreach (@glob($rd . '/patch.php.save*') ?: [] as $sav) {
            if (is_file($sav)) $addFile($sav, 'orphan', ['patch.php.save backup']);
        }
    }

    $traces['files'] = array_values($traces['files']);

    return $traces;
}


function og_patch_cleanup_file_traces(string $path, string $kind, string $backupDir, bool $dryRun): bool
{
    $bk = $backupDir . '/' . md5($path);
    if (is_file($bk)) {
        if ($dryRun) {
            return true;
        }
        return @copy($bk, $path);
    }
    if (!is_file($path)) {
        return false;
    }
    $src = (string)@file_get_contents($path);
    if ($kind === 'htaccess') {
        $out = og_patch_strip_htaccess_offer_guard($src);
    } elseif ($kind === 'php') {
        $out = og_strip_php_offer_guard_blocks($src);
        $out = preg_replace('/<\?php\s*\n@error_reporting\(0\);\s*\n(?:\/\* OfferGuard runtime inject \*\/[\s\S]*?\?>)\s*/', '<?php' . "\n", $out, 1) ?? $out;
    } else {
        $out = og_strip_offer_guard_fragments($src);
    }
    if ($out === $src) {
        return false;
    }
    if ($dryRun) {
        return true;
    }
    // Якщо після зачистки нічого не лишилось (файл містив тільки OG-блок),
    // видалити сам файл, а не залишати порожнього мусора.
    if (trim($out) === '' && ($kind === 'htaccess' || $kind === 'html' || $kind === 'php')) {
        @unlink($path);
        return !is_file($path);
    }

    return @file_put_contents($path, $out, LOCK_EX) !== false;
}


function og_patch_rollback(string $offerPath, string $backupDir, bool $dryRun = false, bool $tracesOnly = false, bool $skipHead = false): void
{
    if (!$skipHead) {
        head($tracesOnly ? 'ОЧИСТКА — OfferGuard traces' : 'ОТКАТ — OfferGuard');
    }
    $offerPath = rtrim(realpath($offerPath) ?: $offerPath, '/');
    if (!is_dir($backupDir)) {
        warn('Бэкапов нет (_og_backup) — восстановление из backup пропущено, снимаем только OfferGuard traces');
        $backupDir = rtrim($backupDir, '/');
    }
    $offerPrefix = $offerPath . '/';
    $metaItems = og_patch_load_backup_meta($backupDir);
    $restored = 0;
    $skipped = 0;

    foreach ($metaItems as $item) {
        $orig = $item['original'];
        $bk = $item['backup'];
        $meta = $item['meta'];
        if (!og_patch_path_under_root($orig, $offerPath)) {
            skip('Пропущено (поза patch root): ' . basename($orig));
            $skipped++;
            continue;
        }
        if (!og_patch_meta_was_patched($meta, $orig, $bk)) {
            skip('Пропущено (не патч OfferGuard): ' . basename($orig));
            $skipped++;
            continue;
        }
        if ($dryRun) {
            ok('[DRY] Восстановлен: ' . str_replace($offerPrefix, '', $orig));
            $restored++;
            continue;
        }
        if (@copy($bk, $orig)) {
            $rel = str_replace($offerPrefix, '', $orig);
            ok('Восстановлен: ' . ($rel !== '' ? $rel : basename($orig)));
            $restored++;
        } else {
            warn('Не удалось восстановить: ' . basename($orig));
        }
    }

    $traces = og_patch_find_offer_guard_traces($offerPath);
    foreach ($traces['files'] as $tf) {
        $fp = $tf['path'];
        $kind = (string)($tf['kind'] ?? '');
        // .og_secret живёт в parent dir (out-of-tree) — гейт `path_under_root` не пускает,
        // но удалять её при откате надо, иначе trace остаётся навечно.
        if ($kind !== 'secret' && !og_patch_path_under_root($fp, $offerPath)) {
            continue;
        }
        $bk = $backupDir . '/' . md5($fp);
        if (is_file($bk)) {
            continue;
        }
        $kind = (string)($tf['kind'] ?? '');
        if ($kind === 'bot-protect') {
            if ($dryRun) {
                ok('[DRY] Удалён trace: bot-protect.php');
            } elseif (@unlink($fp)) {
                ok('Удалён trace: bot-protect.php');
            }
            continue;
        }
        if ($kind === 'secret') {
            if ($dryRun) {
                ok('[DRY] Удалён: .og_secret (out-of-tree)');
            } elseif (@unlink($fp)) {
                ok('Удалён: .og_secret (out-of-tree)');
            }
            continue;
        }
        if ($kind === 'orphan') {
            // OG-named файли вне _og_data (например patch.php.save від vim).
            if ($dryRun) {
                ok('[DRY] Удалён orphan: ' . ($tf['rel'] ?? basename($fp)));
            } elseif (@unlink($fp)) {
                ok('Удалён orphan: ' . ($tf['rel'] ?? basename($fp)));
            }
            continue;
        }
        if (og_patch_cleanup_file_traces($fp, $kind !== '' ? $kind : 'html', $backupDir, $dryRun)) {
            ok(($dryRun ? '[DRY] ' : '') . 'Сняты traces: ' . ($tf['rel'] ?? basename($fp)));
        }
    }

    foreach ($traces['dirs'] as $name => $dp) {
        if ($name === '_og_backup') {
            continue;
        }
        if ($name === '_og_assets') {
            $left = glob($dp . '/*') ?: [];
            if ($left !== []) {
                skip('_og_assets не пуст — оставлен (' . count($left) . ' файл/ов)');
                continue;
            }
        }
        if ($dryRun) {
            ok("[DRY] Удалена папка: $name/");
        } elseif (og_patch_rmdir_recursive($dp)) {
            ok("Удалена папка: $name/");
        }
    }

    foreach (['bot-protect.php', 'robots.txt'] as $f) {
        $fp = $offerPath . '/' . $f;
        $bk = $backupDir . '/' . md5($fp);
        if (!is_file($fp) || is_file($bk)) {
            continue;
        }
        if ($f === 'bot-protect.php' && !og_patch_is_bot_protect_file($fp)) {
            continue;
        }
        if ($f === 'robots.txt') {
            $rob = (string)@file_get_contents($fp);
            $isOg = str_contains($rob, 'OfferGuard')
                || str_contains($rob, 'Disallow: /_og_')
                // эвристика: AI-блок патчера (GPTBot/Claude-Web/anthropic-ai/PerplexityBot)
                || (str_contains($rob, 'GPTBot') && str_contains($rob, 'Claude-Web')
                    && str_contains($rob, 'PerplexityBot'));
            if (!$isOg) {
                continue;
            }
        }
        if ($dryRun) {
            ok("[DRY] Удалён (создан патчем): $f");
        } elseif (@unlink($fp)) {
            ok("Удалён (создан патчем): $f");
        }
    }

    $ht = $offerPath . '/.htaccess';
    if (is_file($ht)) {
        $htBk = $backupDir . '/' . md5($ht);
        if (!is_file($htBk)) {
            $htSrc = (string)@file_get_contents($ht);
            $stripped = og_patch_strip_htaccess_offer_guard($htSrc);
            if ($stripped !== trim($htSrc)) {
                if ($stripped === '') {
                    // .htaccess містив тільки OG блок — видалити файл,
                    // не залишати порожнього (Apache буде skipti, але це сміття).
                    if ($dryRun) {
                        ok('[DRY] .htaccess видалено (містив тільки OG)');
                    } elseif (@unlink($ht)) {
                        ok('.htaccess видалено (містив тільки OG)');
                    }
                } else {
                    if ($dryRun) {
                        ok('[DRY] OfferGuard-блоки сняты с .htaccess');
                    } elseif (@file_put_contents($ht, $stripped . "\n", LOCK_EX) !== false) {
                        ok('OfferGuard-блоки сняты с .htaccess');
                    }
                }
            }
        }
    }

    out(GREEN . "\n  " . ($tracesOnly ? 'Очистка' : 'Откат') . " завершён. Восстановлено: $restored"
        . ($skipped ? GRAY . " (пропущено: $skipped)" : '') . "\n");
}


function og_patch_cleanup(string $offerPath, string $backupDir, bool $dryRun = false): void
{
    head('CLEANUP — повна зачистка OfferGuard');
    $offerPath = rtrim(realpath($offerPath) ?: $offerPath, '/');

    // Sweep тільки offerPath + ВУЗЬКА вибірка з parent (без recursive!).
    // У parent дивимось ТІЛЬКИ: .og_secret, bot-protect.php (наш), _og_data, _og_backup,
    // cloak-папки з маркером, patch.php.save*. НЕ заглядаємо в інші webroot'и поряд
    // (наприклад інші сайти на тому ж серверi).
    $parentOfPatchRoot = dirname($offerPath);
    $sweepRoots = [$offerPath];
    $parentArtifacts = []; // [path => ['kind'=>..., 'rel'=>...]]
    if ($parentOfPatchRoot !== '' && $parentOfPatchRoot !== $offerPath && is_dir($parentOfPatchRoot)
        && $parentOfPatchRoot !== '/' && strlen($parentOfPatchRoot) > 3) {
        // 1) .og_secret (out-of-tree)
        $pSec = $parentOfPatchRoot . '/.og_secret';
        if (is_file($pSec)) {
            $parentArtifacts[$pSec] = ['kind' => 'secret', 'rel' => '.og_secret'];
        }
        // 2) bot-protect.php (наш)
        $pBp = $parentOfPatchRoot . '/bot-protect.php';
        if (og_patch_is_bot_protect_file($pBp)) {
            $parentArtifacts[$pBp] = ['kind' => 'bot-protect', 'rel' => 'bot-protect.php'];
        }
        // 3) patch.php.save* (легасі мусор)
        foreach (@glob($parentOfPatchRoot . '/patch.php.save*') ?: [] as $sav) {
            $parentArtifacts[$sav] = ['kind' => 'orphan', 'rel' => basename($sav)];
        }
        // 4) Orphan og_*.log файли НА верхньому рівні parent (не у sub-сайтах)
        foreach (['og_runtime.log', 'og_blocked.log', 'og_traffic.log', 'og_sessions.log'] as $ln) {
            $lp = $parentOfPatchRoot . '/' . $ln;
            if (is_file($lp)) {
                $parentArtifacts[$lp] = ['kind' => 'orphan', 'rel' => $ln];
            }
        }
        // 5) _og_data, _og_assets, _og_backup ПРЯМО в parent (а не у вкладених)
        $parentDirs = [];
        foreach (['_og_data', '_og_assets', '_og_backup'] as $sd) {
            $dp = $parentOfPatchRoot . '/' . $sd;
            if (is_dir($dp)) $parentDirs[$sd] = $dp;
        }
        // 6) Cloak-папки прямо в parent (з .og_cloak маркером)
        foreach (@glob($parentOfPatchRoot . '/*', GLOB_ONLYDIR) ?: [] as $dp) {
            $name = basename($dp);
            // НЕ чіпаємо webroot'и сусідніх сайтів (public_html та інше)
            if (og_patch_is_generic_web_dir($name)) continue;
            // НЕ чіпаємо сам наш offerPath
            if (rtrim(realpath($dp) ?: $dp, '/') === $offerPath) continue;
            if (is_file($dp . '/.og_cloak')) {
                $parentDirs[$name] = $dp;
            }
        }
        if ($parentArtifacts !== [] || $parentDirs !== []) {
            info('[CLEANUP] Виявлено застарілу інсталяцію в parent: ' . $parentOfPatchRoot
                . ' — будуть сметені: '
                . count($parentArtifacts) . ' файл, ' . count($parentDirs) . ' папок.');
        }
    } else {
        $parentDirs = [];
    }

    // Збираємо traces ТІЛЬКИ з offerPath + вузької вибірки з parent
    $allFiles = [];
    $allDirs = [];
    $t = og_patch_find_offer_guard_traces($offerPath);
    foreach ($t['files'] as $f) {
        $allFiles[(string)$f['path']] = $f;
    }
    foreach ($t['dirs'] as $k => $dp) {
        $allDirs[(string)$dp] = ['name' => $k, 'path' => $dp];
    }
    foreach ($parentArtifacts as $fp => $pa) {
        $allFiles[$fp] = ['path' => $fp, 'rel' => 'parent/' . $pa['rel'], 'kind' => $pa['kind'], 'labels' => ['parent artifact']];
    }
    foreach ($parentDirs as $name => $dp) {
        $allDirs[$dp] = ['name' => 'parent/' . $name, 'path' => $dp];
    }
    if (count($allFiles) === 0 && count($allDirs) === 0) {
        ok('Следов OfferGuard не найдено в ' . implode(', ', $sweepRoots));
        return;
    }
    $traces = ['files' => array_values($allFiles), 'dirs' => array_combine(
        array_map(fn($x) => $x['name'], $allDirs),
        array_map(fn($x) => $x['path'], $allDirs)
    )];

    out(CYAN . "\n  Найдено следов OfferGuard:");
    foreach ($traces['files'] as $tf) {
        $labels = implode(', ', $tf['labels'] ?? ['trace']);
        out(GRAY . '    ' . ($tf['rel'] ?? basename($tf['path'])) . YEL . '  [' . $labels . ']');
    }
    foreach ($traces['dirs'] as $name => $dp) {
        out(GRAY . '    ' . $name . '/' . YEL . '  [patch dir]');
    }

    if (og_patch_cli_is_tty() && !$dryRun) {
        fwrite(STDOUT, "\n  " . YEL . 'Зняти ВСЕ сліди OfferGuard (включаючи _og_backup)? [y/N]: ' . R);
        $line = fgets(STDIN);
        $ans = $line === false ? '' : strtolower(trim($line));
        if (!in_array($ans, ['y', 'yes', 'так', 'да', '1'], true)) {
            warn('Cleanup отменён.');
            return;
        }
    }

    // Phase 1: rollback (восстанавливает HTML/PHP из backup, сносит bot-protect и т.д.)
    if (is_dir($backupDir)) {
        og_patch_rollback($offerPath, $backupDir, $dryRun, true, true);
    }

    // Phase 2: re-scan и доудаление того что осталось
    $traces2 = og_patch_find_offer_guard_traces($offerPath);
    $cleaned = 0;
    $failed = [];
    foreach ($traces2['files'] as $tf) {
        $fp = $tf['path'];
        $kind = (string)($tf['kind'] ?? '');
        $rel = $tf['rel'] ?? basename($fp);
        if ($kind === 'bot-protect' || $kind === 'secret') {
            if ($dryRun) {
                ok("[DRY] Удалён: $rel");
                $cleaned++;
                continue;
            }
            if (!@unlink($fp)) {
                @chmod($fp, 0644);
                if (!@unlink($fp)) {
                    $failed[] = "$rel (unlink permission denied)";
                    continue;
                }
            }
            ok("Удалён: $rel");
            $cleaned++;
            continue;
        }
        if (og_patch_cleanup_file_traces($fp, $kind !== '' ? $kind : 'html', $backupDir, $dryRun)) {
            ok(($dryRun ? '[DRY] ' : '') . "Очищен: $rel");
            $cleaned++;
        } else {
            $failed[] = "$rel (strip failed)";
        }
    }

    // Phase 3: все папки (включая _og_backup на финальном этапе cleanup)
    foreach ($traces2['dirs'] as $name => $dp) {
        if ($dryRun) {
            ok("[DRY] Удалена папка: $name/");
            $cleaned++;
            continue;
        }
        if (og_patch_rmdir_recursive($dp)) {
            ok("Удалена папка: $name/");
            $cleaned++;
        } else {
            $failed[] = "$name/ (rmdir failed)";
        }
    }

    // Phase 4: parent dir стрейчем — .og_secret + cloak-папки в parent
    $parent = dirname($offerPath);
    if ($parent !== '' && $parent !== $offerPath && is_dir($parent)) {
        $parentSec = $parent . '/.og_secret';
        if (is_file($parentSec) && !$dryRun) {
            if (@unlink($parentSec)) {
                ok('Удалён: .og_secret (parent)');
                $cleaned++;
            }
        }
    }

    // Phase 5: МНОЖИНА ПРОХОДІВ верифікації. Якщо після phase 2-4 щось ще
    // лишилось — повторяємо до 3 раз. Це закриває кейси:
    // - вкладені cloak-папки в cloak-папках
    // - файли, які стали orphan після видалення parent dir
    // - htaccess, який стрипнули, але після цього сам файл стал mostly empty
    $finalFiles = [];
    $finalDirs = [];
    for ($pass = 1; $pass <= 3; $pass++) {
        $reCleaned = 0;
        // Re-collect з offerPath + повторно з parent (вузька вибірка артефактів)
        $reFiles = [];
        $reDirs = [];
        if (is_dir($offerPath)) {
            $t = og_patch_find_offer_guard_traces($offerPath);
            foreach ($t['files'] as $f) {
                $reFiles[(string)$f['path']] = $f;
            }
            foreach ($t['dirs'] as $k => $dp) {
                $reDirs[(string)$dp] = ['name' => $k, 'path' => $dp];
            }
        }
        // Знову перевіряємо parent на наші артефакти
        if ($parentOfPatchRoot !== $offerPath && is_dir($parentOfPatchRoot)
            && $parentOfPatchRoot !== '/' && strlen($parentOfPatchRoot) > 3) {
            $pBp = $parentOfPatchRoot . '/bot-protect.php';
            if (og_patch_is_bot_protect_file($pBp)) {
                $reFiles[$pBp] = ['path' => $pBp, 'rel' => 'parent/bot-protect.php', 'kind' => 'bot-protect'];
            }
            $pSec = $parentOfPatchRoot . '/.og_secret';
            if (is_file($pSec)) {
                $reFiles[$pSec] = ['path' => $pSec, 'rel' => 'parent/.og_secret', 'kind' => 'secret'];
            }
            foreach (['_og_data', '_og_assets', '_og_backup'] as $sd) {
                $dp = $parentOfPatchRoot . '/' . $sd;
                if (is_dir($dp)) $reDirs[$dp] = ['name' => 'parent/' . $sd, 'path' => $dp];
            }
            foreach (@glob($parentOfPatchRoot . '/*', GLOB_ONLYDIR) ?: [] as $dp) {
                $name = basename($dp);
                if (og_patch_is_generic_web_dir($name)) continue;
                if (rtrim(realpath($dp) ?: $dp, '/') === $offerPath) continue;
                if (is_file($dp . '/.og_cloak')) {
                    $reDirs[$dp] = ['name' => 'parent/' . $name, 'path' => $dp];
                }
            }
            foreach (@glob($parentOfPatchRoot . '/patch.php.save*') ?: [] as $sav) {
                $reFiles[$sav] = ['path' => $sav, 'rel' => 'parent/' . basename($sav), 'kind' => 'orphan'];
            }
            foreach (['og_runtime.log', 'og_blocked.log', 'og_traffic.log', 'og_sessions.log'] as $ln) {
                $lp = $parentOfPatchRoot . '/' . $ln;
                if (is_file($lp)) {
                    $reFiles[$lp] = ['path' => $lp, 'rel' => 'parent/' . $ln, 'kind' => 'orphan'];
                }
            }
        }
        if ($reFiles === [] && $reDirs === []) {
            // нічого не лишилось — виходимо
            $finalFiles = []; $finalDirs = [];
            break;
        }
        // Видаляємо файли
        foreach ($reFiles as $fp => $tf) {
            $kind = (string)($tf['kind'] ?? '');
            $rel = $tf['rel'] ?? basename($fp);
            if (in_array($kind, ['bot-protect', 'secret', 'orphan'], true)) {
                if (!$dryRun) {
                    if (!@unlink($fp)) { @chmod($fp, 0644); @unlink($fp); }
                }
                if (!is_file($fp)) $reCleaned++;
                continue;
            }
            if (og_patch_cleanup_file_traces($fp, $kind !== '' ? $kind : 'html', $backupDir, $dryRun)) {
                $reCleaned++;
            }
        }
        // Видаляємо папки
        foreach ($reDirs as $dp => $di) {
            if ($dryRun) { $reCleaned++; continue; }
            if (og_patch_rmdir_recursive($dp)) $reCleaned++;
        }
        $cleaned += $reCleaned;
        if ($reCleaned === 0) {
            // Нічого нового не змогли видалити — записуємо як залишки і виходимо
            $finalFiles = array_values($reFiles);
            $finalDirs = $reDirs;
            break;
        }
        if ($pass < 3) {
            info("[CLEANUP] Pass $pass: видалено ще $reCleaned. Перевіряю чи лишилось...");
        }
    }

    // Phase 6: фінальна верифікація + report
    if ($finalFiles === [] && $finalDirs === []) {
        out(GREEN . "\n  ✓ Cleanup чистий. Слідів OfferGuard не залишилось ($cleaned обʼєктів видалено).\n");
        if ($failed !== []) {
            warn('Деякі помилки:');
            foreach ($failed as $f) warn('  ' . $f);
        }
    } else {
        out(YEL . "\n  Cleanup: видалено $cleaned. Залишилось:");
        foreach ($finalFiles as $tf) {
            out(YEL . '    ' . ($tf['rel'] ?? basename($tf['path'])) . GRAY . ' ['
                . ($tf['kind'] ?? '?') . ']');
        }
        foreach ($finalDirs as $dp => $di) {
            out(YEL . '    ' . ($di['name'] ?? basename($dp)) . '/');
        }
        if ($failed !== []) {
            warn('Помилки видалення:');
            foreach ($failed as $f) warn('  ' . $f);
        }
        info('Якщо залишки заважають — запустить як sudo або chown під свого юзера, потім повторите --cleanup.');
    }
}


function og_patch_rmdir_recursive(string $dir): bool
{
    if (!is_dir($dir)) {
        return false;
    }
    try {
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iter as $f) {
            $p = $f->getPathname();
            if ($f->isDir()) {
                if (!@rmdir($p)) {
                    // Спроба №2: підняти права і повторити
                    @chmod($p, 0755);
                    @rmdir($p);
                }
            } else {
                if (!@unlink($p)) {
                    @chmod($p, 0644);
                    @unlink($p);
                }
            }
        }
    } catch (Throwable $e) {
        return false;
    }
    if (!@rmdir($dir)) {
        @chmod($dir, 0755);
        return @rmdir($dir);
    }
    return true;
}


function og_patch_validate_unknown_cli_flags(array $args): void
{
    $knownExact = [
        '--rollback', '--dry-run', '--status', '--verify-copy', '--cleanup', '--bans', '--whitelist',
        '--traffic', '--sessions', '--tail', '--unban', '--allow', '--deny', '--ip', '--class', '--limit',
        '--no-auto-protect', '--minimal', '--scan-deep', '--og-emergency-unbreak', '--og-origin-safe', '--og-no-htaccess',
        '--og-defer-assets=0', '--og-defer-assets=1', '--og-hide-until-unlock=1', '--og-content-wrap=1',
        '--og-htaccess-full=1', '--doctor', '--why', '--watch', '--recovery', '--reset-state', '--verify', '--site-up',
        '--xlog', '--xlog-tail', '--xlog-clear',
    ];
    $knownPrefixes = [
        '--og-', '--canonical-host=', '--xlog-level=', '--xlog-filter=',
    ];
    $valueFlags = ['--unban', '--allow', '--deny', '--ip', '--class', '--limit'];
    $skipNext = false;
    foreach ($args as $a) {
        if ($skipNext) {
            $skipNext = false;
            continue;
        }
        if (!is_string($a) || !str_starts_with($a, '--')) {
            continue;
        }
        if (preg_match('/^--rollback/i', $a)) {
            continue;
        }
        if (in_array($a, $knownExact, true)) {
            if (in_array($a, $valueFlags, true)) {
                $skipNext = true;
            }
            continue;
        }
        foreach ($knownPrefixes as $pfx) {
            if (str_starts_with($a, $pfx)) {
                continue 2;
            }
        }
        $suggest = og_patch_suggest_cli_flag($a);
        fail('Невідомий прапор: ' . $a);
        if ($suggest !== '') {
            warn('Можливо мали на увазі: ' . $suggest);
        }
        out(GRAY . '  Підказка: php patch.php -h');
        exit(1);
    }
}


function og_patch_suggest_cli_flag(string $flag): string
{
    $candidates = [
        '--rollback', '--dry-run', '--status', '--verify-copy', '--cleanup', '--bans', '--whitelist',
        '--traffic', '--sessions', '--minimal', '--scan-deep', '--canonical-host=',
    ];
    $best = '';
    $bestDist = 99;
    foreach ($candidates as $c) {
        $d = levenshtein($flag, $c);
        if ($d < $bestDist) {
            $bestDist = $d;
            $best = $c;
        }
    }

    return ($best !== '' && $bestDist <= 3) ? $best : '';
}


function og_stack_adapter_php(string $root): array
{
    $roots = [];
    foreach (['public_html', 'public', 'www', 'htdocs', 'web', 'html', 'dist', 'out'] as $sub) {
        $p = $root . '/' . $sub;
        if (is_dir($p)) {
            $roots[] = $sub;
        }
    }
    if ($roots === [] && (is_file($root . '/index.php') || is_file($root . '/index.html'))) {
        $roots[] = '.';
    }
    $phpEntry = null;
    $phpHtmlVia = false;
    foreach (array_merge(['.'], $roots) as $rel) {
        $cand = $rel === '.' ? $root . '/index.php' : $root . '/' . $rel . '/index.php';
        if (is_file($cand)) {
            $phpEntry = $rel === '.' ? 'index.php' : $rel . '/index.php';
            $peek = og_patch_peek_file($cand, 65536);
            $phpHtmlVia = og_patch_php_contains_html_output($peek);
            break;
        }
    }
    if ($phpEntry === null) {
        try {
            $iter = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            $iter->setMaxDepth(6);
            $found = [];
            foreach ($iter as $f) {
                if (!$f->isFile() || strtolower($f->getExtension()) !== 'php') {
                    continue;
                }
                $p = $f->getPathname();
                if (og_patch_should_skip_walk_path($p) || !og_patch_is_wp_theme_php_eligible($p)) {
                    continue;
                }
                if (!og_patch_php_contains_html_output(og_patch_peek_file($p, 49152))) {
                    continue;
                }
                $rel = ltrim(str_replace($root . '/', '', $p), '/');
                $prio = str_ends_with($rel, '/index.php') ? 2 : 1;
                $found[$rel] = $prio;
                if (count($found) >= 12) {
                    break;
                }
            }
            if ($found !== []) {
                arsort($found);
                $phpEntry = (string)array_key_first($found);
                $phpHtmlVia = true;
            }
        } catch (Throwable $e) {
        }
    }
    $notes = [];
    if ($phpEntry !== null) {
        $notes[] = 'PHP entry: ' . $phpEntry . ($phpHtmlVia ? ' (html-via-php)' : '') . ' — runtime inject';
    } else {
        $notes[] = 'PHP без html-outlet index — лише HTML + bot-protect';
    }

    return ['stack' => 'php', 'html_roots' => $roots, 'php_entry' => $phpEntry, 'notes' => $notes];
}


function og_stack_adapter_node(string $root): array
{
    $roots = [];
    foreach (['out', 'dist', 'build', '.output/public', 'public', '.next/server/app'] as $sub) {
        if (is_dir($root . '/' . $sub)) {
            $roots[] = $sub;
        }
    }
    $notes = ['Node/Next: патчьте зібраний HTML (out/dist/build), не вихід src/'];
    if (is_file($root . '/package.json')) {
        $pj = strtolower((string)@file_get_contents($root . '/package.json', false, null, 0, 65536));
        if (str_contains($pj, '"next"')) {
            $notes[] = 'Next.js: npm run build && php patch.php ./out';
        }
    }

    return ['stack' => 'node', 'html_roots' => $roots, 'php_entry' => null, 'notes' => $notes];
}


function og_stack_adapter_python(string $root): array
{
    $roots = [];
    foreach (['templates', 'template', 'static', 'public'] as $sub) {
        if (is_dir($root . '/' . $sub)) {
            $roots[] = $sub;
        }
    }
    $notes = ['Django/Flask: templates/ з повним <html> або static після collectstatic'];
    if (is_file($root . '/manage.py')) {
        $notes[] = 'Django: php patch.php ' . ($roots[0] ?? 'templates');
    }

    return ['stack' => 'python', 'html_roots' => $roots, 'php_entry' => null, 'notes' => $notes];
}


function og_stack_adapter_ruby(string $root): array
{
    $roots = [];
    if (is_dir($root . '/app/views')) {
        $roots[] = 'app/views';
    }
    if (is_dir($root . '/public')) {
        $roots[] = 'public';
    }
    $notes = ['Rails: layout з <!DOCTYPE> або public/ для precompiled'];

    return ['stack' => 'ruby', 'html_roots' => $roots, 'php_entry' => null, 'notes' => $notes];
}


function og_stack_adapter_dotnet(string $root): array
{
    $roots = [];
    foreach (['Views', 'Pages', 'wwwroot', 'public'] as $sub) {
        $p = $root . '/' . $sub;
        if (is_dir($p)) {
            $roots[] = $sub;
        }
    }
    foreach ([$root . '/src', $root . '/app'] as $base) {
        if (!is_dir($base)) {
            continue;
        }
        try {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($base, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($it as $d) {
                if (!$d->isDir() || strtolower($d->getFilename()) !== 'views') {
                    continue;
                }
                $rel = ltrim(str_replace($root . '/', '', $d->getPathname()), '/');
                if ($rel !== '' && !in_array($rel, $roots, true)) {
                    $roots[] = $rel;
                }
            }
        } catch (Throwable $e) {
        }
    }
    $notes = ['.NET: *.cshtml з <html>/body або wwwroot'];

    return ['stack' => 'dotnet', 'html_roots' => $roots, 'php_entry' => null, 'notes' => $notes];
}


function og_stack_adapter_java(string $root): array
{
    $roots = [];
    try {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($it as $d) {
            if (!$d->isDir()) {
                continue;
            }
            $pn = str_replace('\\', '/', $d->getPathname());
            if (!preg_match('#/WEB-INF/views$#i', $pn)) {
                continue;
            }
            $rel = ltrim(str_replace($root . '/', '', $pn), '/');
            if ($rel !== '' && !in_array($rel, $roots, true)) {
                $roots[] = $rel;
            }
        }
    } catch (Throwable $e) {
    }
    if (is_dir($root . '/src/main/webapp')) {
        $roots[] = 'src/main/webapp';
    }
    $notes = ['Java/JSP: WEB-INF/views або webapp з JSP layout'];

    return ['stack' => 'java', 'html_roots' => $roots, 'php_entry' => null, 'notes' => $notes];
}


function og_stack_adapter_go(string $root): array
{
    $roots = [];
    foreach (['templates', 'static', 'public', 'web'] as $sub) {
        if (is_dir($root . '/' . $sub)) {
            $roots[] = $sub;
        }
    }
    $notes = ['Go html/template: каталог з .html шаблонами'];

    return ['stack' => 'go', 'html_roots' => $roots, 'php_entry' => null, 'notes' => $notes];
}


function og_stack_adapter_static(string $root): array
{
    $roots = [];
    if (is_file($root . '/index.html') || is_file($root . '/index.htm')) {
        $roots[] = '.';
    }

    return ['stack' => 'static', 'html_roots' => $roots, 'php_entry' => null, 'notes' => ['Статичний HTML у корені патча']];
}


function og_patch_stack_adapters_for_root(string $root): array
{
    $root = rtrim($root, '/');
    $out = [];
    if (is_file($root . '/package.json')) {
        $out[] = og_stack_adapter_node($root);
    }
    if (is_file($root . '/composer.json') || is_file($root . '/wp-config.php') || is_dir($root . '/wp-content')
        || is_file($root . '/index.php')) {
        $out[] = og_stack_adapter_php($root);
    }
    if (is_file($root . '/manage.py') || is_file($root . '/requirements.txt') || is_file($root . '/pyproject.toml')) {
        $out[] = og_stack_adapter_python($root);
    }
    if (is_file($root . '/Gemfile')) {
        $out[] = og_stack_adapter_ruby($root);
    }
    foreach (glob($root . '/*.csproj') ?: [] as $_) {
        $out[] = og_stack_adapter_dotnet($root);
        break;
    }
    if (is_file($root . '/pom.xml') || is_file($root . '/build.gradle') || is_file($root . '/build.gradle.kts')) {
        $out[] = og_stack_adapter_java($root);
    }
    if (is_file($root . '/go.mod')) {
        $out[] = og_stack_adapter_go($root);
    }
    if ($out === [] && (is_file($root . '/index.html') || is_file($root . '/index.htm'))) {
        $out[] = og_stack_adapter_static($root);
    }
    if ($out === []) {
        $out[] = ['stack' => 'unknown', 'html_roots' => ['.'], 'php_entry' => null, 'notes' => ['Маркерів стеку немає — передайте каталог з HTML outlet']];
    }

    return $out;
}


function og_patch_preferred_web_dir_bonus(string $dirPath): int
{
    $bn = strtolower(basename(rtrim($dirPath, '/')));
    $preferred = [
        'public_html' => 18, 'public' => 16, 'www' => 14, 'htdocs' => 14, 'web' => 12,
        'dist' => 12, 'out' => 12, 'build' => 10, 'views' => 8, 'templates' => 8,
        'html' => 6,
    ];

    return $preferred[$bn] ?? 0;
}


function og_patch_score_html_root(string $absRoot): int
{
    if (!is_dir($absRoot)) {
        return 0;
    }
    // Жёсткий boost: если в этом каталоге уже сидит наша установка
    // (bot-protect.php + _og_data), это с большой вероятностью корректный
    // patch root от предыдущего запуска — оставляем его в приоритете.
    // ВАЖНО: бонус НЕ присваиваем родителям через рекурсию — только конкретно
    // этому каталогу, где валидный bot-protect.
    $existingInstallBonus = 0;
    if (is_file($absRoot . '/bot-protect.php') && is_dir($absRoot . '/_og_data')) {
        // Проверяем, что это именно наш bot-protect, не чужой PHP-файл с таким именем.
        $bpHead = @file_get_contents($absRoot . '/bot-protect.php', false, null, 0, 2048);
        if (is_string($bpHead) && str_contains($bpHead, 'OfferGuard')) {
            $existingInstallBonus = 200;
        }
    }
    $score = og_patch_preferred_web_dir_bonus($absRoot) + $existingInstallBonus;
    foreach (['index.html', 'index.htm', 'index.php'] as $idx) {
        if (is_file($absRoot . '/' . $idx)) {
            $score += 40;
            if ($idx === 'index.php') {
                $peek = og_patch_peek_file($absRoot . '/index.php', 65536);
                if (og_patch_php_contains_html_output($peek)) {
                    $score += 12;
                }
            }
        }
    }
    $htmlLike = 0;
    $phpHtml = 0;
    try {
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($absRoot, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        $iter->setMaxDepth(min(6, og_patch_walk_max_depth()));
        $n = 0;
        foreach ($iter as $f) {
            if (!$f->isFile()) {
                continue;
            }
            $p = $f->getPathname();
            if (og_patch_should_skip_walk_path($p)) {
                continue;
            }
            $n++;
            if ($n > 1200) {
                break;
            }
            $bn = strtolower($f->getFilename());
            $ext = strtolower($f->getExtension());
            if (in_array($ext, ['html', 'htm'], true)) {
                $score += 3;
                $htmlLike++;

                continue;
            }
            if ($ext === 'php' || str_ends_with($bn, '.blade.php')) {
                $peek = og_patch_peek_file($p, 32768);
                if (og_patch_php_contains_html_output($peek)) {
                    $score += 4;
                    $phpHtml++;
                } elseif (preg_match('/<\s*html\b|<\s*body\b/i', $peek)) {
                    $score += 2;
                    $htmlLike++;
                }
            } elseif (in_array($ext, ['twig', 'ejs', 'hbs', 'cshtml', 'jsp', 'erb', 'vue'], true)) {
                $score += 2;
                $htmlLike++;
            }
        }
    } catch (Throwable $e) {
    }
    $GLOBALS['og_patch_score_last_counts'] = ['html_like' => $htmlLike, 'php_html' => $phpHtml, 'path' => $absRoot];

    return $score;
}


function og_patch_discover_html_root_candidates(string $inputPath, int $maxDirs = 48): array
{
    $inputPath = rtrim(realpath($inputPath) ?: $inputPath, '/');
    $seen = [];
    $queue = [[$inputPath, 0]];
    $preferredRel = [
        'public_html', 'public', 'www', 'htdocs', 'web', 'dist', 'out', 'build',
        '.output/public', 'templates', 'views', 'app/views', 'static',
    ];
    foreach ($preferredRel as $rel) {
        $abs = $rel === '.' ? $inputPath : $inputPath . '/' . $rel;
        if (is_dir($abs)) {
            $queue[] = [rtrim(realpath($abs) ?: $abs, '/'), 0];
        }
    }
    $parent = dirname($inputPath);
    if ($parent !== $inputPath && is_dir($parent)) {
        foreach ($preferredRel as $rel) {
            $abs = $parent . '/' . $rel;
            if (is_dir($abs)) {
                $queue[] = [rtrim(realpath($abs) ?: $abs, '/'), 0];
            }
        }
    }
    while ($queue !== [] && count($seen) < $maxDirs) {
        [$dir, $depth] = array_shift($queue);
        if (!is_dir($dir) || isset($seen[$dir])) {
            continue;
        }
        $seen[$dir] = og_patch_score_html_root($dir);
        if ($depth >= 3) {
            continue;
        }
        try {
            $it = new DirectoryIterator($dir);
            foreach ($it as $di) {
                if ($di->isDot() || !$di->isDir()) {
                    continue;
                }
                $name = $di->getFilename();
                if (str_starts_with($name, '.')) {
                    continue;
                }
                $child = $di->getPathname();
                if (og_patch_should_skip_walk_path($child)) {
                    continue;
                }
                $queue[] = [$child, $depth + 1];
            }
        } catch (Throwable $e) {
        }
    }
    arsort($seen);

    return $seen;
}


function og_patch_php_inject_targets(string $offerPath, array $phpFiles, array $collectMeta = [], int $maxFiles = 5): array
{
    $root = rtrim(realpath($offerPath) ?: $offerPath, '/');
    $candidates = [];
    foreach ($phpFiles as $p) {
        if (!is_file($p) || og_patch_should_skip_walk_path($p)) {
            continue;
        }
        if (!og_patch_is_wp_theme_php_eligible($p)) {
            continue;
        }
        $peek = og_patch_peek_file($p);
        if (!og_patch_php_contains_html_output($peek) && !og_patch_is_html_like_file($p, $peek)) {
            continue;
        }
        $prio = 10;
        $dir = rtrim(realpath(dirname($p)) ?: dirname($p), '/');
        $bn = strtolower(basename($p));
        if ($dir === $root && $bn === 'index.php') {
            $prio = 1000;
        } elseif ($dir === $root) {
            $prio = 500;
        } elseif (preg_match('#/(?:wp-content/)?themes/[^/]+/#i', str_replace('\\', '/', $p))) {
            $prio = 300;
        } elseif (preg_match('#/(?:landing|landings|pages?|lp)/#i', str_replace('\\', '/', $p))) {
            $prio = 200;
        }
        $relDepth = substr_count(str_replace($root . '/', '', str_replace('\\', '/', $p)), '/');
        $prio -= min(80, $relDepth * 15);
        $candidates[] = ['path' => $p, 'prio' => $prio];
    }
    usort($candidates, static fn(array $a, array $b): int => $b['prio'] <=> $a['prio']);
    $out = [];
    foreach ($candidates as $c) {
        $out[] = $c['path'];
        if (count($out) >= $maxFiles) {
            break;
        }
    }
    if ($out === [] && !empty($collectMeta['php_html_files']) && is_array($collectMeta['php_html_files'])) {
        $out = array_slice($collectMeta['php_html_files'], 0, $maxFiles);
    }

    return $out;
}


function og_patch_resolve_patch_directory(
    string $inputPath,
    bool $cliPathExplicit = false,
    bool $skipAutoPromote = false
): array {
    $inputPath = rtrim(realpath($inputPath) ?: $inputPath, '/');
    $meta = [
        'input' => $inputPath, 'changed' => false, 'reason' => '', 'notes' => [],
        'adapters' => [], 'alternate_roots' => [], 'cli_path_explicit' => $cliPathExplicit,
        'skip_auto_promote' => $skipAutoPromote,
    ];
    $candidates = [$inputPath => ['rel' => '.', 'score' => og_patch_score_html_root($inputPath)]];
    $counts = $GLOBALS['og_patch_score_last_counts'] ?? [];
    if (!empty($counts['path']) && (string)$counts['path'] === $inputPath) {
        info('[OfferGuard] scan: found ' . (int)($counts['html_like'] ?? 0) . ' html-like, '
            . (int)($counts['php_html'] ?? 0) . ' php-html in ' . $inputPath);
    }
    $searchRoots = [$inputPath];
    $parent = dirname($inputPath);
    if ($parent !== $inputPath && is_dir($parent)) {
        $searchRoots[] = $parent;
    }
    foreach ($searchRoots as $sr) {
        foreach (og_patch_stack_adapters_for_root($sr) as $adapter) {
            $meta['adapters'][] = $adapter;
            foreach ($adapter['html_roots'] as $rel) {
                $abs = $rel === '.' ? $sr : $sr . '/' . $rel;
                $abs = rtrim(realpath($abs) ?: $abs, '/');
                if (!is_dir($abs)) {
                    continue;
                }
                $candidates[$abs] = ['rel' => $rel, 'score' => og_patch_score_html_root($abs)];
            }
        }
    }
    foreach (og_patch_discover_html_root_candidates($inputPath) as $discPath => $discScore) {
        if (!isset($candidates[$discPath])) {
            $candidates[$discPath] = ['rel' => str_replace($inputPath . '/', '', $discPath), 'score' => $discScore];
        } else {
            $candidates[$discPath]['score'] = max($candidates[$discPath]['score'], $discScore);
        }
    }
    uasort($candidates, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);
    $bestPath = $inputPath;
    $bestScore = $candidates[$inputPath]['score'] ?? 0;
    foreach ($candidates as $path => $info) {
        if ($info['score'] > $bestScore) {
            $bestScore = $info['score'];
            $bestPath = $path;
        }
    }
    $rank = 0;
    foreach ($candidates as $path => $info) {
        if ($path === $bestPath || $info['score'] < 4) {
            continue;
        }
        if ($rank >= 4) {
            break;
        }
        $relShow = str_replace($inputPath . '/', '', $path);
        if ($relShow === $path) {
            $relShow = basename($path);
        }
        $meta['alternate_roots'][] = ['path' => $path, 'rel' => $relShow, 'score' => $info['score']];
        $rank++;
    }
    if ($bestPath !== $inputPath && $bestScore >= 4) {
        $relShow = str_replace($inputPath . '/', '', $bestPath);
        if ($relShow === $bestPath) {
            $relShow = basename($bestPath);
        }
        $inputIsWebRoot = og_patch_is_generic_web_dir(basename($inputPath));
        // КРИТИЧНО: «promote» вверх (в parent dir над input) ломает развёртку,
        // если input уже document root (Apache/nginx серверы видят только то, что
        // под webroot). Поэтому если bestPath — предок input ИЛИ input — webroot,
        // не поднимаемся, что бы ни говорил scoring.
        $bestIsAncestor = $inputPath !== $bestPath && str_starts_with($inputPath . '/', $bestPath . '/');
        if ($bestIsAncestor || $inputIsWebRoot) {
            $meta['notes'][] = 'Не піднімаємо patch root вище web-root: залишено ' . $inputPath
                . ' (альтернатива: ' . $relShow . ', score ' . $bestScore . ')';
            $bestPath = $inputPath;
            $bestScore = $candidates[$inputPath]['score'] ?? 0;
            $meta['recommended_patch_dir'] = $bestPath;
            $meta['patch_score'] = $bestScore;
            if ($meta['alternate_roots'] !== []) {
                $altParts = [];
                foreach ($meta['alternate_roots'] as $ar) {
                    $altParts[] = ($ar['rel'] ?? '?') . ' (score ' . (int)($ar['score'] ?? 0) . ')';
                }
                $meta['notes'][] = 'Альтернативні patch root: ' . implode(', ', $altParts);
            }
            if (!empty($meta['adapters'][0]['notes'])) {
                foreach ($meta['adapters'][0]['notes'] as $n) {
                    if (!in_array($n, $meta['notes'], true)) {
                        $meta['notes'][] = $n;
                    }
                }
            }
            return [$bestPath, $meta];
        }
        // АВТО-PROMOTE DOWN (без prompt'а): если input НЕ webroot, а bestPath —
        // это descendant input'а И сам является webroot (public_html/www/…), значит
        // user явно указал на parent dir, а реальный webroot — внутри.
        // Это безопасное направление: уходим в child, где сидит реальная установка.
        // Без этого фикса патчер пишет bot-protect в parent, Apache его не видит,
        // bot-protect.php дублируется в child, ключи не совпадают → blank page.
        $bestIsDescendant = $inputPath !== $bestPath && str_starts_with($bestPath . '/', $inputPath . '/');
        $bestIsWebRoot = og_patch_is_generic_web_dir(basename($bestPath));
        if ($bestIsDescendant && $bestIsWebRoot) {
            $meta['changed'] = true;
            $meta['reason'] = 'auto-descend to webroot ' . $relShow . ' (score ' . $bestScore . ')';
            $meta['notes'][] = 'Авто-перехід на webroot: ' . $relShow
                . ' (' . $inputPath . ' — parent dir, не document root)';
            $meta['recommended_patch_dir'] = $bestPath;
            $meta['patch_score'] = $bestScore;
            if ($meta['alternate_roots'] !== []) {
                $altParts = [];
                foreach ($meta['alternate_roots'] as $ar) {
                    $altParts[] = ($ar['rel'] ?? '?') . ' (score ' . (int)($ar['score'] ?? 0) . ')';
                }
                $meta['notes'][] = 'Альтернативні patch root: ' . implode(', ', $altParts);
            }
            if (!empty($meta['adapters'][0]['notes'])) {
                foreach ($meta['adapters'][0]['notes'] as $n) {
                    if (!in_array($n, $meta['notes'], true)) {
                        $meta['notes'][] = $n;
                    }
                }
            }
            return [$bestPath, $meta];
        }
        $allowPromote = !$skipAutoPromote && !$cliPathExplicit;
        if (!$allowPromote && $cliPathExplicit && !$skipAutoPromote && og_patch_cli_is_tty()) {
            fwrite(STDOUT, "\n  " . YEL . 'Знайдено ширший patch root: ' . $bestPath
                . ' (score ' . $bestScore . '). Замінити явний шлях ' . $inputPath . '? [y/N]: ' . R);
            $line = fgets(STDIN);
            $ans = $line === false ? '' : strtolower(trim($line));
            $allowPromote = in_array($ans, ['y', 'yes', 'так', 'да', '1'], true);
        }
        $recommendedScore = $bestScore;
        if ($allowPromote) {
            $meta['changed'] = true;
            $meta['reason'] = 'auto html-outlet (score ' . $bestScore . ')';
            $meta['notes'][] = 'Рекомендований patch root: ' . $relShow;
        } else {
            $bestPath = $inputPath;
            $bestScore = $candidates[$inputPath]['score'] ?? 0;
            if ($skipAutoPromote) {
                $meta['notes'][] = 'Maintenance mode: залишено CLI patch root без auto-widen';
            } elseif ($inputIsWebRoot) {
                $meta['notes'][] = 'Залишено web-root (public_html/www/…); альтернатива: ' . $relShow
                    . ' (score ' . $recommendedScore . ')';
            } else {
                $meta['notes'][] = 'Залишено явний CLI patch root; альтернатива: ' . $relShow
                    . ' (score ' . $recommendedScore . ')';
            }
        }
    }
    if ($meta['alternate_roots'] !== []) {
        $altParts = [];
        foreach ($meta['alternate_roots'] as $ar) {
            $altParts[] = ($ar['rel'] ?? '?') . ' (score ' . (int)($ar['score'] ?? 0) . ')';
        }
        $meta['notes'][] = 'Альтернативні patch root: ' . implode(', ', $altParts);
    }
    $meta['recommended_patch_dir'] = $bestPath;
    $meta['patch_score'] = $bestScore;
    if (!empty($meta['adapters'][0]['notes'])) {
        foreach ($meta['adapters'][0]['notes'] as $n) {
            if (!in_array($n, $meta['notes'], true)) {
                $meta['notes'][] = $n;
            }
        }
    }

    return [$bestPath, $meta];
}


function og_patch_emergency_htaccess_disable(string $htFile, bool $dryRun = false): bool
{
    if (!is_file($htFile)) {
        return false;
    }
    $existing = (string)@file_get_contents($htFile);
    if ($existing === '') {
        return false;
    }
    $hadOg = str_contains($existing, '[OfferGuard:')
        || str_contains($existing, 'OfferGuard v6')
        || str_contains($existing, 'bot-protect.php');
    $stripped = preg_replace(
        '/# \[OfferGuard:universal-runtime\][\s\S]*?# \[\/OfferGuard:universal-runtime\]\s*/',
        '',
        $existing
    ) ?? $existing;
    $stripped = preg_replace(
        '/# \[OfferGuard:emergency-safe\][\s\S]*?# \[\/OfferGuard:emergency-safe\]\s*/',
        '',
        $stripped
    ) ?? $stripped;
    $stripped = preg_replace(
        '/# OfferGuard v6[\s\S]*?(?=\n# (?!\[OfferGuard)|\z)/',
        '',
        $stripped,
        1
    ) ?? $stripped;
    $stripped = trim($stripped);
    if (!$hadOg && $stripped === trim($existing)) {
        return false;
    }
    if ($stripped === '') {
        $payload = <<<'HTA'
# [OfferGuard:emergency-safe]
# OfferGuard rewrite block removed — site should load. Re-patch with --og-htaccess=1 only if needed.
# [/OfferGuard:emergency-safe]
HTA;
    } else {
        $payload = $stripped . "\n\n# [OfferGuard:emergency-safe] runtime rewrites disabled by --og-emergency-unbreak\n";
    }
    if ($dryRun) {
        return true;
    }
    if (!is_dir(dirname($htFile))) {
        return false;
    }
    $bak = $htFile . '.og-emergency-' . date('Ymd-His') . '.bak';
    @copy($htFile, $bak);
    file_put_contents($htFile, rtrim($payload) . "\n", LOCK_EX);

    return true;
}


function og_patch_htaccess_rewrite_skip_conds(): string
{
    return "    # Do not rewrite ErrorDocument targets or Apache internal redirects\n"
        . "    RewriteCond %{ENV:REDIRECT_STATUS} ^$\n"
        . "    RewriteCond %{REQUEST_URI} !^/(document_errors|errors)/ [NC]\n"
        . "    RewriteCond %{REQUEST_URI} !/(403|404|50x)\\.html$ [NC]\n"
        . "    RewriteCond %{REQUEST_URI} !^/favicon\\.ico$ [NC]\n"
        . "    RewriteCond %{REQUEST_URI} !^/robots\\.txt$ [NC]\n";
}


function og_patch_universal_htaccess_block(?bool $safeMode = null): string
{
    if ($safeMode === null) {
        $safeMode = (bool)($GLOBALS['OG_PATCH_HTACCESS_SAFE'] ?? true);
    }
    $skip = og_patch_htaccess_rewrite_skip_conds();
    $missing_static = "    # Missing static assets → bot-protect.php serves encrypted version or 404\n"
        . "    RewriteCond %{REQUEST_FILENAME} !-f\n"
        . "    RewriteRule ^.+\\.(css|js|mjs|ico|woff2?|ttf|eot|png|jpg|jpeg|gif|svg|webp|avif|bmp|map)$ bot-protect.php [L,QSA,NC]\n";
    if ($safeMode) {
        return <<<'HTA'
# [OfferGuard:universal-runtime]
# Safe runtime: only live-gate + mirror probe (no bait/trap rewrites on ErrorDocument paths)
<IfModule mod_rewrite.c>
    RewriteEngine On
HTA
            . $skip
            . <<<'HTA'
    RewriteRule ^_site/(v|r|s|a|x)$ bot-protect.php [L,QSA]
    RewriteRule ^_og_(ping|pf_ok|page_token)$ bot-protect.php [L,QSA]
    RewriteRule ^(_og_mirror_probe|\.well-known/og-mirror-probe)$ bot-protect.php [L,QSA]
HTA
            . $missing_static
            . <<<'HTA'
</IfModule>
# [/OfferGuard:universal-runtime]
HTA;
    }

    return <<<'HTA'
# [OfferGuard:universal-runtime]
# Full runtime: API + traps (skips ErrorDocument / internal redirect URIs)
<IfModule mod_rewrite.c>
    RewriteEngine On
HTA
        . $skip
        . <<<'HTA'
    RewriteRule ^_site/(v|r|s|a|x)$ bot-protect.php [L,QSA]
    RewriteRule ^_og_(ping|pf_ok|page_token)$ bot-protect.php [L,QSA]
    RewriteRule ^_site/(l|m|c)(/.*)?$ bot-protect.php [L,QSA]
    RewriteRule ^(_og_trap|_og_mirror_probe|\.well-known/og-trap|\.well-known/og-mirror-probe|download-site|mirror-site)(/.*)?$ bot-protect.php [L,QSA]
    RewriteRule ^_og_bait(/.*)?$ bot-protect.php [L,QSA]
HTA
        . $missing_static
        . <<<'HTA'
</IfModule>
# [/OfferGuard:universal-runtime]
HTA;
}


function og_patch_write_universal_htaccess_rules(string $htFile, bool $dryRun, bool $writeFullIfMissing = true): void
{
    $safeMode = (bool)($GLOBALS['OG_PATCH_HTACCESS_SAFE'] ?? true);
    $block = og_patch_universal_htaccess_block($safeMode);
    $full = $GLOBALS['OG_PATCH_HTACCESS_FULL'] ?? '';
    $allowFullCreate = !empty($GLOBALS['OG_PATCH_HTACCESS_FULL_CREATE']);
    if (!is_file($htFile)) {
        if ($dryRun) {
            return;
        }
        $useFull = $writeFullIfMissing && $full !== '' && !$safeMode && $allowFullCreate;
        $payload = $useFull ? $full : $block;
        file_put_contents($htFile, rtrim($payload) . "\n", LOCK_EX);

        return;
    }
    $existing = (string)@file_get_contents($htFile);
    if ($dryRun) {
        return;
    }
    if (str_contains($existing, '[OfferGuard:universal-runtime]')) {
        $merged = preg_replace(
            '/# \[OfferGuard:universal-runtime\][\s\S]*?# \[\/OfferGuard:universal-runtime\]\s*/',
            rtrim($block) . "\n\n",
            $existing,
            1
        );
        if (is_string($merged) && $merged !== $existing) {
            file_put_contents($htFile, $merged, LOCK_EX);
        }

        return;
    }
    // ВАЖЛИВО: prepend, не append. WordPress/Laravel `.htaccess` зазвичай має
    // catch-all `RewriteRule . /index.php [L]` в кінці, який зловить `/_site/v`
    // якщо наші правила підуть після нього. Specific шляхи `^_site/(v|r|s)$`
    // не конфліктують з користувацькими — каждое запитання, яке не матчить наш
    // дуже вузький патерн, спокійно падає вниз до існуючих правил.
    file_put_contents($htFile, rtrim($block) . "\n\n" . ltrim($existing), LOCK_EX);
}


function og_patch_emit_runtime_snippet_md(string $offerPath, array $detect, bool $dryRun): void
{
    $host = (string)($detect['canonical_from_path'] ?? '');
    $type = (string)($detect['offer_type'] ?? 'unknown');
    $rec = (string)($detect['recommended_patch_dir'] ?? $offerPath);
    $md = "# OfferGuard — вбудувати runtime (API-only / без локального HTML)\n\n"
        . "Патчер не знайшов HTML outlet у `$offerPath`. Захист копій потребує HTML, який бачить браузер.\n\n"
        . "## Детект стеку\n\n"
        . "- `offer_type`: **{$type}**\n"
        . "- Рекомендований patch root: `{$rec}`\n\n"
        . "## Що зробити\n\n"
        . "1. Додайте в **головний layout** (один раз на сторінку):\n\n"
        . "```html\n<script src=\"/_site/s\" defer></script>\n```\n\n"
        . "2. Розмістіть `bot-protect.php` у document root оффера (поруч із статикою).\n\n"
        . "3. **Apache** — у `.htaccess` document root:\n\n"
        . "```apache\n" . og_patch_universal_htaccess_block() . "\n```\n\n"
        . "4. **nginx** (Node/Python за reverse proxy):\n\n"
        . "```nginx\nlocation ~ ^/_site/(v|r|s)$ {\n    include fastcgi_params;\n    fastcgi_param SCRIPT_FILENAME \$document_root/bot-protect.php;\n    fastcgi_pass unix:/run/php/php-fpm.sock;\n}\nlocation = /bot-protect.php { /* php-fpm */ }\n```\n\n"
        . "5. Перезапустіть патч по каталогу з шаблонами: `php patch.php /path/to/templates`\n\n"
        . ($host !== '' ? "Canonical host: **{$host}** (`--canonical-host={$host}`)\n" : "Задайте `--canonical-host=ваш-домен.com`\n");
    $out = rtrim($offerPath, '/') . '/og_runtime_embed_instructions.md';
    if ($dryRun) {
        warn('[DRY] Буде записано: og_runtime_embed_instructions.md');

        return;
    }
    file_put_contents($out, $md, LOCK_EX);
    warn('API-only / без HTML: інструкція → og_runtime_embed_instructions.md');
}


function og_patch_detect_canonical_host_from_paths(string $offerRoot, string $cwd): string
{
    $pick = static function (string $label): string {
        $h = og_patch_normalize_host($label);

        return og_patch_host_looks_like_domain($h) ? $h : '';
    };

    $cwdResolved = @realpath($cwd);
    if ($cwdResolved !== false && $cwdResolved !== '') {
        $fromCwd = $pick(basename($cwdResolved));
        if ($fromCwd !== '') {
            return $fromCwd;
        }
    }

    $offerResolved = @realpath($offerRoot);
    if ($offerResolved !== false && $offerResolved !== '') {
        $offerBase = basename($offerResolved);
        if (og_patch_is_generic_web_dir($offerBase)) {
            $parent = dirname($offerResolved);
            if ($parent !== $offerResolved && $parent !== '') {
                $fromParent = $pick(basename($parent));
                if ($fromParent !== '') {
                    return $fromParent;
                }
            }
        }
        if (!og_patch_is_generic_web_dir($offerBase)) {
            $fromOffer = $pick($offerBase);
            if ($fromOffer !== '') {
                return $fromOffer;
            }
        }
    }

    return '';
}

function og_patch_cli_is_tty(): bool
{
    if (function_exists('stream_isatty')) {
        return @stream_isatty(STDIN);
    }
    if (function_exists('posix_isatty')) {
        return @posix_isatty(STDIN);
    }

    return false;
}

function og_patch_cli_clear_screen(): void
{
    if (!og_patch_cli_is_tty()) {
        return;
    }

    if (DIRECTORY_SEPARATOR === '\\') {
        @system('cls');
        return;
    }

    if (@system('tput clear 2>/dev/null') !== false) {
        return;
    }

    echo "\033[2J\033[H";
}

function og_patch_cli_pause_and_clear(): void
{
    if (!og_patch_cli_is_tty()) {
        return;
    }

    fwrite(STDOUT, GRAY . "\n  Натисніть Enter для повернення до меню... " . R);
    fgets(STDIN);
    og_patch_cli_clear_screen();
}

function og_patch_has_canonical_host_arg(array $args): bool
{
    foreach ($args as $a) {
        if (!is_string($a) || !str_starts_with($a, '--canonical-host=')) {
            continue;
        }
        if (trim(substr($a, strlen('--canonical-host='))) !== '') {
            return true;
        }
    }

    return false;
}

function og_patch_prompt_canonical_host(string $detected, ?callable $askFn = null, bool $require = false): string
{
    $detected = og_patch_canonical_host_choice($detected);
    if (!og_patch_cli_is_tty()) {
        return $detected;
    }

    $normalize = static function (string $raw): string {
        $raw = preg_replace('#^https?://#i', '', $raw) ?? $raw;
        $raw = strtolower(trim($raw, "/ \t\r\n"));
        if ($raw === '' || !preg_match('/^[a-z0-9.-]+(:\d+)?$/', $raw)) {
            return '';
        }

        return og_patch_canonical_host_choice($raw);
    };

    $tries = 0;
    do {
        $def = $detected;
        if ($askFn !== null) {
            $in = trim((string)$askFn('Canonical host', $def));
            if ($in === '') {
                $in = $def;
            }
        } else {
            fwrite(STDOUT, "\n  Canonical host" . ($def !== '' ? " [{$def}]" : '') . ': ');
            $line = fgets(STDIN);
            $in = $line === false ? $def : trim($line);
            if ($in === '') {
                $in = $def;
            }
            og_patch_cli_clear_screen();
        }
        $chosen = $normalize($in);
        if ($chosen !== '') {
            return $chosen;
        }
        if (!$require && $detected !== '') {
            return $detected;
        }
        $tries++;
        if ($require && $tries < 3) {
            warn('Введіть домен (напр. example.com) або Enter — залишити визначений.');
        }
    } while ($require && $tries < 3);

    return $detected;
}

function og_patch_canonical_host_choice(string $host): string
{
    $h = og_patch_normalize_host($host);
    if ($h === '' || !og_patch_host_looks_like_domain($h)) {
        return $h;
    }

    return og_patch_host_apex($h);
}

function og_patch_resolve_canonical_host(
    array $args,
    array $htmlFiles,
    bool $autoDetect = true,
    string $offerRoot = '',
    string $cwd = ''
): string {
    foreach ($args as $a) {
        if (!is_string($a) || !str_starts_with($a, '--canonical-host=')) {
            continue;
        }
        $raw = trim(substr($a, strlen('--canonical-host=')));
        if ($raw === '') {
            continue;
        }
        if (preg_match('#^https?://#i', $raw)) {
            $h = parse_url($raw, PHP_URL_HOST);
            if (is_string($h) && $h !== '') {
                $chosen = og_patch_canonical_host_choice($h);
                info('[OfferGuard] canonical host з CLI --canonical-host=: ' . $chosen);

                return $chosen;
            }
        }
        $chosen = og_patch_canonical_host_choice($raw);
        info('[OfferGuard] canonical host з CLI --canonical-host=: ' . $chosen);

        return $chosen;
    }

    if (!$autoDetect) {
        return '';
    }

    $cwdPath = $cwd !== '' ? $cwd : (string)(getcwd() ?: '');

    if ($offerRoot !== '') {
        $fromFolder = og_patch_detect_canonical_host_from_paths($offerRoot, $cwdPath);
        if ($fromFolder !== '') {
            $chosen = og_patch_canonical_host_choice($fromFolder);
            $offerBase = basename((string)(@realpath($offerRoot) ?: $offerRoot));
            if (og_patch_is_generic_web_dir($offerBase)) {
                info('[OfferGuard] canonical host з батьківської папки (/' . $offerBase . ' → ' . $chosen . ')');
            } else {
                info('[OfferGuard] canonical host з папки оффера/cwd: ' . $chosen);
            }

            return $chosen;
        }

        $fromBp = og_patch_read_canonical_from_bot_protect($offerRoot);
        if ($fromBp !== '') {
            $chosen = og_patch_canonical_host_choice($fromBp);
            info('[OfferGuard] canonical host з bot-protect.php: ' . $chosen);

            return $chosen;
        }
    }

    foreach ($htmlFiles as $p) {
        $src = (string)@file_get_contents($p);
        if ($src === '') {
            continue;
        }
        $h = og_patch_read_og_origin_meta($src);
        if ($h !== '' && $h !== 'og_canonical_host_change_me') {
            $chosen = og_patch_canonical_host_choice($h);
            info('[OfferGuard] canonical host з meta og-origin-host (' . basename($p) . '): ' . $chosen);

            return $chosen;
        }
    }

    foreach ($htmlFiles as $p) {
        $src = (string)@file_get_contents($p);
        if ($src === '') {
            continue;
        }
        $h = og_patch_read_og_canonical_comment($src);
        if ($h !== '' && og_patch_host_looks_like_domain($h)) {
            $chosen = og_patch_canonical_host_choice($h);
            info('[OfferGuard] canonical host з HTML-коментаря og:canonical (' . basename($p) . '): ' . $chosen);

            return $chosen;
        }
    }

    $det = og_patch_detect_canonical_from_html($htmlFiles);
    $host = (string)($det['host'] ?? '');
    if ($host === '') {
        return '';
    }
    if (!empty($det['ambiguous'])) {
        $list = implode(', ', (array)($det['candidates'] ?? []));
        warn('[OfferGuard] кілька доменів у HTML (' . $list . ') — обрано найчастіший: ' . $host);
    } else {
        $srcLabel = (string)($det['source'] ?? 'html');
        info('[OfferGuard] canonical host з посилань HTML (' . $srcLabel . '): ' . $host);
    }

    return og_patch_canonical_host_choice($host);
}

function og_patch_clean_og_assets_dir(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    @unlink($dir . '/.htaccess');
    @unlink($dir . '/index.html');
    foreach (glob($dir . '/*') ?: [] as $f) {
        if (is_file($f)) {
            @unlink($f);
        }
    }
}


function og_patch_write_og_assets_apache_config(
    string $assetDir,
    string $canonicalHostLower,
    bool $refererGate,
    bool $nocacheHeaders
): void {
    if (!$refererGate && !$nocacheHeaders) {
        return;
    }
    $lines = [
        '# [OfferGuard:og-assets] generated by patch.php — do not hand-edit (re-run patch to change)',
        'RewriteEngine On',
    ];
    if ($refererGate) {
        $h = strtolower(trim($canonicalHostLower));
        if ($h === '') {
            warn('--og-assets-htaccess=1 потребує --canonical-host= (або meta og-origin-host) — Referer-гейт не записано.');
        } else {
            
            $refRe = '^https?://(www\\.)?' . preg_quote($h, '/') . '(:\d+)?(/|\?|$)';
            $lines[] = '<IfModule mod_setenvif.c>';
            $lines[] = '<IfModule mod_authz_core.c>';
            $lines[] = 'SetEnvIf Referer "' . $refRe . '" og_asset_referer_ok=1';
            $lines[] = '<FilesMatch "^.*$">';
            $lines[] = '  Require all denied';
            $lines[] = '  Require env og_asset_referer_ok';
            $lines[] = '</FilesMatch>';
            $lines[] = '</IfModule>';
            $lines[] = '</IfModule>';
        }
        @file_put_contents($assetDir . '/index.html', "<!DOCTYPE html><meta charset=\"utf-8\"><title></title>\n", LOCK_EX);
    }
    if ($nocacheHeaders) {
        $lines[] = '<IfModule mod_headers.c>';
        $lines[] = '<FilesMatch "\\.(js|mjs|png|webp|gif|jpe?g)$">';
        $lines[] = '  Header set Cache-Control "private, max-age=0, must-revalidate"';
        $lines[] = '</FilesMatch>';
        $lines[] = '</IfModule>';
    }
    @file_put_contents($assetDir . '/.htaccess', implode("\n", $lines) . "\n", LOCK_EX);
}


function og_framework_profiles(): array
{
    return [
        'max' => [
            'auto_protect_full'           => true,
            'framework_profile'           => 'max',
            'early_gate'                  => true,
            'meta_og_origin_host'         => true,
            'expected_host'               => true,
            'og_content_visible'          => false,
            'scrape_harden_og_content'    => true,
            'defer_assets'                => true,
            'encrypt_js'                  => true,
            'encrypt_body'                => false,
            'runtime_encrypt_shell'       => false,
            'inline_js_fallback'          => true,
            'assets_htaccess_referer'     => true,
            'assets_nocache'              => true,
            'reload_grace'                => true,
            'reload_grace_per_min'        => 3,
            'live_pub_rotate'             => true,
            'live_hook_one_tab'           => true,
            'copy_guard_strict'           => true,
            'canonical_asset_urls'        => true,
            'neutralize_relative_urls'    => true,
            'csp_frame_ancestors'         => true,
            'html_nocache_htaccess'       => true,
            'host_reverify_js'            => true,
            'host_reverify_ms'            => 4500,
            'copy_defense_noscript'       => true,
            'origin_head_guards_soft'     => true,
            'copy_live_inline_defense'    => false,
            'copy_live_origin_defer_ms'   => 2000,
            'copy_family_reject'          => true,
            'copy_self_destruct_js'       => true,
            'copy_server_reject_html'     => false,
            'trap_links'                  => true,
            'preflight_on'                => false,
            'sec_fetch_strict'            => true,
            'og_webhook_validate'         => true,
            'webhook_mode'                => 'notify',
            'soft_fail_open'              => true,
            'live_required'               => false,
            'client_ls_max'               => 3,
            'copy_ui_lock_css'            => false,
            'copy_body_oncopy_block'      => false,
            'copy_hotkey_block'           => false,
            'copy_hotkey_origin_soft'     => false,
            'html_minify'                 => false,
            'obfuscate_head_guards'       => false,
            'origin_session_strengthen'   => true,
            'origin_ajax_content_gate'    => false,
            'patch_all_html_trees'        => true,
            'htaccess_safe_mode'          => true,
            // КРИТИЧНО: за замовчуванням НЕ пишемо .htaccess. Це адаптивний режим:
            // bot-protect.php працює напряму як `/bot-protect.php?_og_ep=...`,
            // JS-гард має fallback на цей шлях, не потребує rewrite-правил.
            // Якщо юзер хоче `.htaccess` для clean URLs `/_site/{v,r,s}` —
            // вмикається явно через `--og-htaccess=1`. Це уникає конфліктів
            // з HestiaCP/CyberPanel/Plesk де `.htaccess` глобально кастомізований.
            'htaccess_write'              => false,
            'php_inject'                  => false,
        ],
    ];
}


function og_patch_detect_framework_hints(string $offerPath, array &$stackWeights): array
{
    $root = rtrim($offerPath, '/');
    $hints = [];
    $boost = static function (string $k, int $w) use (&$stackWeights): void {
        $stackWeights[$k] = ($stackWeights[$k] ?? 0) + $w;
    };
    $readHead = static function (string $path, int $max = 65536): string {
        if (!is_file($path)) {
            return '';
        }
        $s = @file_get_contents($path, false, null, 0, $max);

        return is_string($s) ? $s : '';
    };
    if (is_file($root . '/package.json')) {
        $boost('node', 3);
        $pj = strtolower($readHead($root . '/package.json'));
        if (str_contains($pj, '"next"') || str_contains($pj, '/next')) {
            $hints[] = 'next';
            $boost('node', 1);
        }
        if (str_contains($pj, '"nuxt"') || preg_match('#@nuxt/|"nuxt":#', $pj) === 1) {
            $hints[] = 'nuxt';
            $boost('node', 1);
        }
        if (str_contains($pj, '"vue"') || str_contains($pj, '/vue')) {
            $hints[] = 'vue';
        }
    }
    if (is_file($root . '/composer.json')) {
        $boost('php', 2);
        $cj = strtolower($readHead($root . '/composer.json'));
        if (preg_match('#laravel/framework|"laravel/|illuminate/support#', $cj) === 1) {
            $hints[] = 'laravel';
            $boost('php', 2);
        }
    }
    if (is_file($root . '/artisan')) {
        $hints[] = 'laravel';
        $boost('php', 2);
    }
    if (is_file($root . '/wp-config.php') || is_dir($root . '/wp-content')) {
        $hints[] = 'wordpress';
        $boost('php', 3);
    }
    $req = $root . '/requirements.txt';
    $pyproj = $root . '/pyproject.toml';
    if (is_file($req) || is_file($pyproj)) {
        $boost('python', 2);
        $pypeek = strtolower($readHead($req) . "\n" . $readHead($pyproj));
        if (str_contains($pypeek, 'django') || is_file($root . '/manage.py')) {
            $hints[] = 'django';
            $boost('python', 2);
        }
        if (str_contains($pypeek, 'flask')) {
            $hints[] = 'flask';
            $boost('python', 1);
        }
    } elseif (is_file($root . '/manage.py')) {
        $hints[] = 'django';
        $boost('python', 3);
    }
    if (is_file($root . '/Gemfile')) {
        $boost('ruby', 2);
        $gf = strtolower($readHead($root . '/Gemfile'));
        if (str_contains($gf, 'rails')) {
            $hints[] = 'rails';
            $boost('ruby', 2);
        }
    }
    if (is_file($root . '/go.mod')) {
        $boost('go', 3);
    }
    if (is_file($root . '/pom.xml') || is_file($root . '/build.gradle') || is_file($root . '/build.gradle.kts')) {
        $boost('java', 3);
    }
    $dotnetHits = 0;
    foreach ([$root, $root . '/src', $root . '/app', $root . '/web'] as $dir) {
        if (!is_dir($dir)) {
            continue;
        }
        foreach (glob($dir . '/*.csproj') ?: [] as $cs) {
            $dotnetHits++;
            $csp = strtolower($readHead((string)$cs, 24576));
            if (str_contains($csp, 'microsoft.aspnetcore') || str_contains($csp, 'microsoft.net.sdk.web')) {
                $hints[] = 'dotnet_mvc';
                $boost('dotnet', 4);

                break 2;
            }
        }
    }
    if ($dotnetHits > 0 && !in_array('dotnet_mvc', $hints, true)) {
        $boost('dotnet', 2);
    }
    $hints = array_values(array_unique($hints));

    return $hints;
}


final class OgFramework
{
    public static function detect(string $offerPath, array $htmlFiles, array $phpFiles, array $collectMeta = [], array $patchMeta = []): array
    {
        $root = rtrim($offerPath, '/');
        $adapters = $patchMeta['adapters'] ?? og_patch_stack_adapters_for_root($root);
        if (!is_array($adapters)) {
            $adapters = [];
        }
        $adapterStacks = [];
        $adapterHtmlRoots = [];
        $adapterNotes = [];
        $phpEntry = null;
        foreach ($adapters as $ad) {
            if (!is_array($ad)) {
                continue;
            }
            $adapterStacks[] = (string)($ad['stack'] ?? '');
            foreach ($ad['html_roots'] ?? [] as $hr) {
                $adapterHtmlRoots[] = (string)$hr;
            }
            foreach ($ad['notes'] ?? [] as $nt) {
                $adapterNotes[] = (string)$nt;
            }
            if ($phpEntry === null && !empty($ad['php_entry'])) {
                $phpEntry = (string)$ad['php_entry'];
            }
        }
        $adapterStacks = array_values(array_unique(array_filter($adapterStacks)));
        $adapterHtmlRoots = array_values(array_unique($adapterHtmlRoots));
        $adapterNotes = array_values(array_unique($adapterNotes));
        $hasIndexPhp = is_file($root . '/index.php');
        $hasIndexHtml = is_file($root . '/index.html') || is_file($root . '/index.htm');
        $jsHints = 0;
        $sample = array_slice($htmlFiles, 0, 12);
        foreach ($sample as $p) {
            $src = @file_get_contents($p);
            if (!is_string($src) || $src === '') {
                continue;
            }
            if (preg_match('/\b(id|class)\s*=\s*["\'][^"\']*\b(app|root|__next|ng-app)\b/i', $src)) {
                $jsHints++;
            }
            if (preg_match('/\b(type\s*=\s*["\']module["\']|import\s*\(|webpack|vite|react|vue|angular)\b/i', $src)) {
                $jsHints++;
            }
            if (preg_match('/<script[^>]+src\s*=\s*["\'][^"\']*\.(m?js|bundle)/i', $src)) {
                $jsHints++;
            }
        }
        $stackWeights = [
            'php'    => count($phpFiles) > 0 ? 1 : 0,
            'node'   => 0,
            'python' => 0,
            'ruby'   => 0,
            'dotnet' => 0,
            'java'   => 0,
            'go'     => 0,
        ];
        $frameworkHints = og_patch_detect_framework_hints($offerPath, $stackWeights);

        foreach (array_keys($collectMeta['template_ext_counts'] ?? []) as $slug) {
            $sl = strtolower((string)$slug);
            if (in_array($sl, ['jsp', 'jspf'], true)) {
                $stackWeights['java'] = max($stackWeights['java'] ?? 0, 2);
            }
            if ($sl === 'cshtml' || $sl === 'aspx' || $sl === 'asp') {
                $stackWeights['dotnet'] = max($stackWeights['dotnet'] ?? 0, 2);
            }
            if ($sl === 'twig' || $sl === 'blade.php') {
                $stackWeights['php'] = max($stackWeights['php'] ?? 0, 2);
            }
            if (in_array($sl, ['ejs', 'hbs', 'tsx', 'jsx', 'vue'], true)) {
                $stackWeights['node'] = max($stackWeights['node'] ?? 0, 2);
            }
            if ($sl === 'jinja2') {
                $stackWeights['python'] = max($stackWeights['python'] ?? 0, 2);
            }
            if ($sl === 'erb') {
                $stackWeights['ruby'] = max($stackWeights['ruby'] ?? 0, 2);
            }
        }
        foreach ($htmlFiles as $hf) {
            if (preg_match('/\.py$/i', $hf)) {
                $stackWeights['python'] = max($stackWeights['python'] ?? 0, 2);

                break;
            }
        }
        if (is_file($root . '/web.config')) {
            $stackWeights['dotnet'] = max($stackWeights['dotnet'] ?? 0, 1);
        }

        $strong = [];
        foreach ($stackWeights as $k => $w) {
            if ((int)$w >= 2) {
                $strong[] = $k;
            }
        }
        $strong = array_values(array_unique($strong));
        $offerType = 'unknown';
        if (count($strong) >= 2) {
            $offerType = 'mixed';
        } elseif (count($strong) === 1) {
            $offerType = $strong[0];
        } else {
            if (count($htmlFiles) > 0) {
                $offerType = 'static';
            } elseif (count($phpFiles) > 0 || $hasIndexPhp) {
                $offerType = 'php';
            }
        }
        if ($offerType === 'php' && $hasIndexPhp && ($hasIndexHtml || $jsHints >= 2) && count($strong) === 0) {
            $offerType = 'mixed';
        }
        $htmlDirs = [];
        foreach ($htmlFiles as $p) {
            $rel = str_replace($root . '/', '', $p);
            $htmlDirs[dirname($rel)] = true;
        }

        $rootMarkers = [];
        foreach (['package.json', 'composer.json', 'requirements.txt', 'pyproject.toml', 'Gemfile', 'go.mod', 'pom.xml', 'build.gradle', 'build.gradle.kts'] as $rm) {
            if (is_file($root . '/' . $rm)) {
                $rootMarkers[] = $rm;
            }
        }
        if (is_file($root . '/manage.py')) {
            $rootMarkers[] = 'manage.py';
        }
        if (is_file($root . '/artisan')) {
            $rootMarkers[] = 'artisan';
        }
        if (is_file($root . '/wp-config.php')) {
            $rootMarkers[] = 'wp-config.php';
        }

        $recommendedPatch = (string)($patchMeta['recommended_patch_dir'] ?? $root);
        if ($recommendedPatch === '' || !is_dir($recommendedPatch)) {
            $recommendedPatch = $root;
        }

        return [
            'canonical_from_path'   => og_patch_detect_canonical_host_from_paths($offerPath, (string)(getcwd() ?: '')),
            'offer_type'            => $offerType,
            'framework_hints'       => $frameworkHints,
            'stack_adapters'        => $adapterStacks,
            'adapter_html_roots'    => $adapterHtmlRoots,
            'adapter_notes'         => $adapterNotes,
            'php_entry'             => $phpEntry,
            'recommended_patch_dir' => $recommendedPatch,
            'patch_input_dir'       => (string)($patchMeta['input'] ?? $root),
            'has_index_php'         => $hasIndexPhp,
            'has_index_html'        => $hasIndexHtml,
            'js_spa_hints'          => $jsHints,
            'html_count'            => count($htmlFiles),
            'php_count'             => count($phpFiles),
            'html_dir_count'        => count($htmlDirs),
            'template_ext_counts'   => $collectMeta['template_ext_counts'] ?? [],
            'collect_walk_trunc'    => !empty($collectMeta['walk_truncated']),
            'collect_html_trunc'    => !empty($collectMeta['html_truncated']),
            'root_markers'          => array_values(array_unique($rootMarkers)),
        ];
    }

    public static function profile(array $detect, string $canonicalHost, array $base = []): array
    {
        $profiles = og_framework_profiles();
        $profile = $profiles['max'] ?? [];
        if ($base !== []) {
            $profile = array_merge($profile, $base);
        }
        $type = (string)($detect['offer_type'] ?? 'static');
        $hints = $detect['framework_hints'] ?? [];
        if (!is_array($hints)) {
            $hints = [];
        }
        $jsSpa = (int)($detect['js_spa_hints'] ?? 0);
        $highJsStack = in_array($type, ['mixed', 'node'], true)
            || in_array('next', $hints, true)
            || in_array('nuxt', $hints, true)
            || in_array('vue', $hints, true)
            || ($type === 'static' && $jsSpa >= 3);
        if ($highJsStack) {
            $profile['encrypt_js'] = true;
            if (empty($profile['origin_head_guards_soft'])) {
                $profile['copy_live_inline_defense'] = true;
                $profile['early_gate'] = true;
            }
            $profile['inline_js_fallback'] = true;
            $profile['defer_assets'] = true;
            $profile['host_reverify_js'] = true;
            $profile['host_reverify_ms'] = max(5200, (int)($profile['host_reverify_ms'] ?? 4500));
        }
        $pyHeavy = $type === 'python' || in_array('django', $hints, true) || in_array('flask', $hints, true);
        $pyTemplates = !empty(($detect['template_ext_counts']['jinja2'] ?? null))
            || (int)($detect['html_count'] ?? 0) > 0;
        if ($pyHeavy || ($pyTemplates && $type === 'python')) {
            $profile['neutralize_relative_urls'] = true;
            $profile['canonical_asset_urls'] = true;
            $profile['defer_assets'] = true;
            $profile['encrypt_js'] = true;
        }
        if ($type === 'ruby' || in_array('rails', $hints, true)) {
            $profile['neutralize_relative_urls'] = true;
            $profile['canonical_asset_urls'] = true;
            $profile['defer_assets'] = true;
        }
        $javaMvc = $type === 'java' || !empty(($detect['template_ext_counts']['jsp'] ?? null)) || !empty(($detect['template_ext_counts']['jspf'] ?? null));
        $dotMvc = $type === 'dotnet' || !empty(($detect['template_ext_counts']['cshtml'] ?? null))
            || in_array('dotnet_mvc', $hints, true);
        if ($javaMvc || $dotMvc) {
            $profile['patch_all_html_trees'] = true;
            $profile['scrape_harden_og_content'] = true;
            $profile['defer_assets'] = true;
            $profile['canonical_asset_urls'] = true;
        }
        if ($type === 'php' || !empty($detect['has_index_php'])) {
            $profile['runtime_encrypt_shell'] = true;
            $profile['origin_session_strengthen'] = true;
        }
        if (($type === 'static' && empty($detect['has_index_php'])) || in_array($type, ['java', 'dotnet'], true)) {
            $profile['patch_all_html_trees'] = true;
        }
        if ($type === 'unknown') {
            warn('[OfferGuard] stack=unknown applying universal max; ensure HTML outlet is patched');
        }
        if ($canonicalHost !== '' && og_patch_is_local_dev_host($canonicalHost)) {
            $profile['copy_hotkey_origin_soft'] = true;
            $profile['soft_fail_open'] = true;
        }

        return $profile;
    }

    public static function applyHtmlCopyUx(string $html, string $canonicalHost, array $profile): string
    {
        $lc = strtolower(trim($canonicalHost));
        if ($lc === '' || empty($profile['auto_protect_full'])) {
            return $html;
        }
        if (!empty($profile['copy_ui_lock_css']) || !empty($profile['copy_body_oncopy_block'])) {
            $html = og_patch_inject_copy_scrape_ux_layers($html, $lc, $profile);
        }
        if (!empty($profile['html_minify'])) {
            $html = og_patch_minify_html($html);
        }

        return $html;
    }

    public static function verify(
        array $htmlFiles,
        string $canonicalHost,
        array $profile,
        string $offerPath,
        bool $encryptBodyPath,
        bool $encryptJsOn,
        bool $hardFail = true
    ): void {
        og_patch_verify_protection($htmlFiles, $canonicalHost, $profile, $offerPath, $hardFail);
    }

    public static function writeAppliedLog(string $dataDir, array $detect, array $profile, bool $dryRun): void
    {
        if ($dryRun || !is_dir($dataDir)) {
            @mkdir($dataDir, 0700, true);
        }
        if ($dryRun) {
            return;
        }
        $payload = [
            'ts'      => time(),
            'detect'  => $detect,
            'profile' => array_filter($profile, static fn($v): bool => $v !== false && $v !== '' && $v !== null),
            'layers'  => array_keys(array_filter($profile, static fn($v): bool => !empty($v))),
        ];
        @file_put_contents(
            rtrim($dataDir, '/') . '/og_framework_applied.json',
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            LOCK_EX
        );
    }
}


function og_patch_minify_html(string $html): string
{
    if (!preg_match('/<html\b/i', $html)) {
        return $html;
    }
    $parts = preg_split('/(<script\b[^>]*>[\s\S]*?<\/script>|<style\b[^>]*>[\s\S]*?<\/style>|<pre\b[^>]*>[\s\S]*?<\/pre>|<textarea\b[^>]*>[\s\S]*?<\/textarea>)/i', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    if (!is_array($parts)) {
        return $html;
    }
    $out = '';
    foreach ($parts as $i => $chunk) {
        if ($i % 2 === 1) {
            $out .= $chunk;
            continue;
        }
        $chunk = preg_replace('/<!--(?!\s*\[OfferGuard)[\s\S]*?-->/', '', $chunk) ?? $chunk;
        $chunk = preg_replace('/>\s+</', '><', $chunk) ?? $chunk;
        $chunk = preg_replace('/\s{2,}/', ' ', $chunk) ?? $chunk;
        $out .= trim($chunk);
    }

    return $out;
}


function og_patch_obfuscate_inline_snippets(string $js): string
{
    $map = [
        'expHost' => '_e',
        'permKill' => '_pk',
        'perm' => '_p',
        'copyCtx' => '_cc',
        'localCopy' => '_lc',
        'storageCopy' => '_sc',
        'nuclear' => '_nk',
        'markReject' => '_mr',
        'stopTimers' => '_st',
        'familyBad' => '_fb',
        'probeTok' => '_pt',
        'probeLive' => '_pl',
        'scopedSk' => '_ss',
        'stripHeadAssets' => '_sha',
        'stripCopyDom' => '_scd',
        'blankDoc' => '_bd',
        'kill' => '_k',
        'tick' => '_tk',
    ];
    foreach ($map as $from => $to) {
        $js = preg_replace('/\bfunction\s+' . preg_quote($from, '/') . '\s*\(/', 'function ' . $to . '(', $js) ?? $js;
        $js = preg_replace('/\b' . preg_quote($from, '/') . '\s*\(/', $to . '(', $js) ?? $js;
    }

    return $js;
}


function og_patch_inject_copy_scrape_ux_layers(string $html, string $canonicalHostLower, array $profile): string
{
    if (preg_match('/\[OfferGuard:copy-ui\]/i', $html)) {
        $html = preg_replace('/<!--\s*\[OfferGuard:copy-ui\]\s*-->.*?<!--\s*\[\/OfferGuard:copy-ui\]\s*-->\s*/is', '', $html) ?? $html;
    }
    $css = '';
    if (!empty($profile['copy_ui_lock_css'])) {
        $css .= '#og-content.og-copy-lock,#og-content.og-copy-lock *{user-select:none!important;-webkit-user-select:none!important;}'
            . 'html[data-og-copy-lock]{user-select:none!important;-webkit-user-select:none!important;}';
    }
    $bodyAttr = '';
    if (!empty($profile['copy_body_oncopy_block'])) {
        $bodyAttr = ' oncopy="return window.__ogBodyCopyGuard&&window.__ogBodyCopyGuard(event)"'
            . ' oncut="return window.__ogBodyCopyGuard&&window.__ogBodyCopyGuard(event)"';
    }
    $guardJs = '';
    if ($bodyAttr !== '') {
        $eh = json_encode(strtolower(trim($canonicalHostLower)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $hmFn = og_patch_js_host_match_fn();
        $defFn = og_patch_js_definitive_copy_fn();
        $guardJs = og_patch_wrap_inline_script(
            '(function(){' . $hmFn . $defFn
            . 'var EH=' . $eh . ';'
            . 'window.__ogBodyCopyGuard=function(ev){try{if(!_ogDefCopy(EH))return true;}catch(e){return true;}'
            . 'try{if(ev&&ev.preventDefault)ev.preventDefault();}catch(e2){}return false;};})();',
            'og-copy-body-guard'
        );
    }
    $block = '<!-- [OfferGuard:copy-ui] -->';
    if ($css !== '') {
        $block .= '<style id="og-copy-ui-css">' . $css . '</style>';
    }
    if ($guardJs !== '') {
        $block .= $guardJs;
    }
    $block .= '<!-- [/OfferGuard:copy-ui] -->';
    if (preg_match('/<head\b[^>]*>/i', $html, $m, PREG_OFFSET_CAPTURE)) {
        $pos = $m[0][1] + strlen($m[0][0]);
        $html = substr($html, 0, $pos) . "\n" . $block . substr($html, $pos);
    }
    if ($bodyAttr !== '' && preg_match('/<body\b([^>]*)>/i', $html, $bm)) {
        $attrs = (string)($bm[1] ?? '');
        if (stripos($attrs, 'oncopy=') === false) {
            $html = preg_replace('/<body\b[^>]*>/i', '<body' . $attrs . $bodyAttr . '>', $html, 1) ?? $html;
        }
    }

    return $html;
}


function og_patch_obfuscate_guard_block(string $block, array $profile): string
{
    if (empty($profile['obfuscate_head_guards']) || $block === '') {
        return $block;
    }
    return (string)preg_replace_callback(
        '/<script\b([^>]*)>([\s\S]*?)<\/script>/i',
        static function (array $m): string {
            $attrs = (string)($m[1] ?? '');
            $inner = og_patch_obfuscate_inline_snippets((string)($m[2] ?? ''));
            $id = '';
            if (preg_match('/\bid\s*=\s*(["\'])([^"\']+)\1/i', $attrs, $im)) {
                $id = $im[2];
            }

            return og_patch_wrap_inline_script($inner, $id);
        },
        $block
    );
}


function og_patch_apply_auto_protect_profile_to_run(
    bool $autoProtect,
    string $canonicalHost,
    array $args,
    bool $ogEncryptJsExplicit,
    bool $ogEncryptBodyExplicit,
    bool $ogAssetsNocacheExplicit,
    bool $ogAssetsHtaccessExplicit,
    bool $ogAssetsAsExplicit,
    bool &$ogEncryptJs,
    bool &$ogEncryptBody,
    bool &$ogAssetsNocache,
    bool &$ogAssetsHtaccess,
    string &$ogAssetsAs,
    bool &$deferLandingAssets,
    bool &$hideOgContentUntilUnlock,
    string &$ogWebhookMode,
    string $ogWebhookUrlPatch = ''
): array {
    if (!$autoProtect || $canonicalHost === '') {
        return [[], false];
    }
    $profile = og_patch_auto_protect_profile();
    if (!$ogEncryptJsExplicit) {
        $ogEncryptJs = !empty($profile['encrypt_js']) && function_exists('openssl_encrypt');
    }
    if (!$ogEncryptBodyExplicit) {
        $ogEncryptBody = !empty($profile['encrypt_body']);
    }
    if (!$ogAssetsNocacheExplicit) {
        $ogAssetsNocache = !empty($profile['assets_nocache']);
    }
    if (!$ogAssetsHtaccessExplicit) {
        $ogAssetsHtaccess = !empty($profile['assets_htaccess_referer']);
    }
    if (!$ogAssetsAsExplicit && $ogEncryptJs) {
        $ogAssetsAs = 'png';
    }
    if (strtolower(trim($ogWebhookMode)) === 'notify' && !empty($profile['webhook_mode'])) {
        $wm = strtolower((string)$profile['webhook_mode']);
        if ($wm === 'authoritative' && $ogWebhookUrlPatch !== '') {
            $ogWebhookMode = 'authoritative';
        } elseif ($wm === 'notify') {
            $ogWebhookMode = 'notify';
        }
    }
    $deferOff = in_array('--og-defer-assets=0', $args, true);
    $deferOn = in_array('--og-defer-assets=1', $args, true);
    if (!$deferOff && ($deferOn || !empty($profile['defer_assets']))) {
        $deferLandingAssets = true;
    }
    $hideExplicit = in_array('--og-hide-until-unlock=1', $args, true)
        || in_array('--og-content-wrap=1', $args, true);
    if (!$hideExplicit) {
        $hideOgContentUntilUnlock = empty($profile['og_content_visible']);
    }

    return [$profile, true];
}


function og_patch_auto_protect_profile(): array
{
    $profiles = og_framework_profiles();

    return $profiles['max'] ?? [];
}


function og_patch_apply_auto_profile_config(string $jsBlockHtml, array $profile): string
{
    if (empty($profile['auto_protect_full'])) {
        return $jsBlockHtml;
    }
    $flags = [
        'deferAssets'              => !empty($profile['defer_assets']),
        'softFailOpen'             => !empty($profile['soft_fail_open']),
        'liveRequired'             => !empty($profile['live_required']),
        'liveHookOneTab'           => !empty($profile['live_hook_one_tab']),
        'copyGuardStrict'          => !empty($profile['copy_guard_strict']),
        'copyDefenseHostReverify'  => !empty($profile['host_reverify_js']),
        'copyDefenseHotkeys'       => !empty($profile['copy_hotkey_block']),
        'copyHotkeyOriginSoft'     => !empty($profile['copy_hotkey_origin_soft']),
        'autoProtectFull'          => true,
    ];
    $out = $jsBlockHtml;
    if (!empty($profile['host_reverify_ms'])) {
        $hrMs = max(2500, (int)$profile['host_reverify_ms']);
        if (preg_match('/\bhostReverifyMs\s*:/', $out)) {
            $r = preg_replace('/\bhostReverifyMs\s*:\s*\d+/', 'hostReverifyMs:' . $hrMs, $out, 1);
            if ($r !== null) {
                $out = $r;
            }
        }
    }
    foreach ($flags as $key => $val) {
        $jsVal = $val ? 'true' : 'false';
        if (preg_match('/\b' . preg_quote($key, '/') . '\s*:/', $out)) {
            $r = preg_replace('/\b' . preg_quote($key, '/') . '\s*:\s*\w+/', $key . ':' . $jsVal, $out, 1);
            if ($r !== null) {
                $out = $r;
            }
            continue;
        }
        $r = preg_replace('/(\bcopyGuardStrict\s*:\s*\w+,)/', '$1' . "\n    {$key}:{$jsVal},", $out, 1);
        if ($r === null) {
            $r = preg_replace('/(\bsoftFailOpen\s*:\s*\w+,)/', '$1' . "\n    {$key}:{$jsVal},", $out, 1);
        }
        if ($r !== null) {
            $out = $r;
        }
    }
    if (!empty($profile['client_ls_max'])) {
        $lsMax = max(1, (int)$profile['client_ls_max']);
        if (preg_match('/\blsMax\s*:/', $out)) {
            $r = preg_replace('/\blsMax\s*:\s*\d+/', 'lsMax:' . $lsMax, $out, 1);
            if ($r !== null) {
                $out = $r;
            }
        }
    }

    return $out;
}


function og_patch_wrap_inline_js_fallback(string $jsBlockHtml): string
{
    if (!preg_match('/<script>\s*([\s\S]*?)<\/script>/i', $jsBlockHtml, $m)) {
        return $jsBlockHtml;
    }
    $core = trim((string)$m[1]);
    $wrapped = "(function(){\"use strict\";\n"
        . "if(window.__ogGuardLoaded)return;\n"
        . "function __ogInlineRun(){\n"
        . "if(window.__ogGuardLoaded)return;\n"
        . $core . "\n"
        . "}\n"
        . "if(window.__ogBootstrapFailed){__ogInlineRun();return;}\n"
        . "setTimeout(function(){\n"
        . "if(!window.__ogGuardLoaded){window.__ogBootstrapFailed=1;__ogInlineRun();}\n"
        . "},1400);\n"
        . "})();";

    return og_patch_wrap_inline_script("\n" . $wrapped . "\n");
}

function og_patch_apply_bot_live_pub_config(string $botProtect): string
{
    if (preg_match("/'live_pub_rotate'\\s*=>/", $botProtect)) {
        $out = preg_replace("/'live_pub_rotate'\\s*=>\\s*\\w+/", "'live_pub_rotate'     => true", $botProtect, 1);

        return $out ?? $botProtect;
    }
    $out = preg_replace(
        "/('live_pub_ttl'\\s*=>\\s*\\d+,\\s*\\n)/",
        "$1    'live_pub_rotate'     => true,\n",
        $botProtect,
        1
    );

    return $out ?? $botProtect;
}

function og_patch_summarize_auto_protect(array $profile, string $canonicalHost): string
{
    $parts = ['max-protect', 'host=' . $canonicalHost];
    $labels = [
        'early_gate' => 'early-gate',
        'meta_og_origin_host' => 'og-origin-meta',
        'expected_host' => 'expectedHost',
        'defer_assets' => 'defer-assets',
        'encrypt_js' => 'encrypt-js+bootstrap',
        'inline_js_fallback' => 'inline-fallback',
        'assets_nocache' => 'assets-nocache',
        'assets_htaccess_referer' => 'referer-htaccess',
        'encrypt_body' => 'encrypt-body',
        'reload_grace' => 'reload-grace',
        'live_pub_rotate' => 'live-pub-rotate',
        'live_hook_one_tab' => '1tab-per-hook',
        'copy_guard_strict' => 'copy-guard',
        'canonical_asset_urls' => 'canonical-assets',
        'neutralize_relative_urls' => 'neutralize-rel',
        'csp_frame_ancestors' => 'csp-frame',
        'html_nocache_htaccess' => 'html-nocache',
        'host_reverify_js' => 'host-reverify',
        'copy_defense_noscript' => 'noscript-harden',
        'origin_head_guards_soft' => 'origin-soft-head',
        'copy_live_inline_defense' => 'copy-live-inline',
        'copy_family_reject' => 'copy-family-reject',
        'htaccess_safe_mode' => 'htaccess-safe',
        'htaccess_write' => 'htaccess-write',
        'php_inject' => 'php-inject',
        'copy_self_destruct_js' => 'copy-self-destruct',
        'copy_server_reject_html' => 'copy-server-reject',
        'copy_ui_lock_css' => 'copy-ui-css',
        'copy_body_oncopy_block' => 'body-oncopy',
        'copy_hotkey_block' => 'copy-hotkeys',
        'html_minify' => 'html-minify',
        'obfuscate_head_guards' => 'obfusc-head',
        'origin_session_strengthen' => 'origin-session+',
        'trap_links' => 'trap-links',
        'preflight_on' => 'preflight',
        'sec_fetch_strict' => 'sec-fetch-strict',
        'og_webhook_validate' => 'webhook-validate',
        'soft_fail_open' => 'softFailOpen',
        'live_required' => 'live-required',
    ];
    foreach ($labels as $k => $label) {
        if (!empty($profile[$k])) {
            $parts[] = $label;
        }
    }
    if (empty($profile['og_content_visible'])) {
        $parts[] = 'og-content-hidden';
    }
    if (!empty($profile['scrape_harden_og_content'])) {
        $parts[] = 'scrape-harden';
    }
    if (!empty($profile['reload_grace_per_min'])) {
        $parts[] = 'reload-grace-' . (int)$profile['reload_grace_per_min'] . '/min';
    }
    if (!empty($profile['client_ls_max'])) {
        $parts[] = 'lsMax-' . (int)$profile['client_ls_max'];
    }
    $wm = strtolower((string)($profile['webhook_mode'] ?? 'notify'));
    $parts[] = $wm === 'authoritative' ? 'webhook-authoritative' : 'webhook-notify';

    return implode(', ', $parts);
}


function og_patch_expected_host_json(string $canonicalHostLower): string
{
    return json_encode(strtolower(trim($canonicalHostLower)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}


function og_patch_html_has_expected_host(string $html, string $canonicalHostLower): bool
{
    $lc = strtolower(trim($canonicalHostLower));
    if ($lc === '') {
        return true;
    }
    $ehJson = og_patch_expected_host_json($lc);
    $hostJs = preg_quote($lc, '/');
    if (preg_match('/\bexpectedHost\s*:\s*' . preg_quote($ehJson, '/') . '\b/', $html) === 1) {
        return true;
    }
    if (preg_match('/\bexpectedHost\s*:\s*"' . $hostJs . '"/', $html) === 1) {
        return true;
    }
    if (preg_match("/\bexpectedHost\s*:\s*'" . $hostJs . "'/", $html) === 1) {
        return true;
    }
    if (preg_match('/\b(?:var\s+)?EH\s*=\s*' . preg_quote($ehJson, '/') . '\b/', $html) === 1) {
        return true;
    }
    if (preg_match('/\b(?:var\s+)?EH\s*=\s*"' . $hostJs . '"/', $html) === 1) {
        return true;
    }
    if (preg_match("/\b(?:var\s+)?EH\s*=\s*'" . $hostJs . "'/", $html) === 1) {
        return true;
    }
    if (preg_match('/\bname\s*=\s*["\']og-expected-host["\'][^>]*\bcontent\s*=\s*["\']' . $hostJs . '["\']/i', $html) === 1) {
        return true;
    }
    if (preg_match('/\bcontent\s*=\s*["\']' . $hostJs . '["\'][^>]*\bname\s*=\s*["\']og-expected-host["\']/i', $html) === 1) {
        return true;
    }
    if (preg_match('/\bdata-og-expected-host\s*=\s*["\']' . $hostJs . '["\']/i', $html) === 1) {
        return true;
    }
    $hasOriginMeta = preg_match('/\bname\s*=\s*["\']og-origin-host["\'][^>]*\bcontent\s*=\s*["\']' . $hostJs . '["\']/i', $html) === 1
        || preg_match('/\bcontent\s*=\s*["\']' . $hostJs . '["\'][^>]*\bname\s*=\s*["\']og-origin-host["\']/i', $html) === 1;
    if ($hasOriginMeta
        && (
            preg_match('/\bid\s*=\s*["\']og-copy-reject["\']/i', $html) === 1
            || stripos($html, '[OfferGuard:copy-reject]') !== false
        )) {
        return true;
    }

    return false;
}


function og_patch_inject_expected_host_meta(string $html, string $canonicalHostLower): string
{
    $lc = strtolower(trim($canonicalHostLower));
    if ($lc === '') {
        return $html;
    }
    $hesc = htmlspecialchars($lc, ENT_QUOTES, 'UTF-8');
    if (preg_match('/<meta\b[^>]*\bname\s*=\s*["\']og-expected-host["\']/i', $html)) {
        return (string)(preg_replace_callback(
            '/<meta(\s[^>]*\bname\s*=\s*(["\'])og-expected-host\2[^>]*)>/i',
            static function (array $m) use ($hesc): string {
                $inner = (string)($m[1] ?? '');
                $tag = '<meta' . $inner . '>';
                if (preg_match('/\bcontent\s*=\s*(["\'])/', $tag, $qm)) {
                    $q = $qm[1];

                    return (string)(preg_replace('/\bcontent\s*=\s*["\'][^"\']*["\']/', 'content=' . $q . $hesc . $q, $tag, 1) ?? $tag);
                }

                return '<meta' . $inner . ' content="' . $hesc . '">';
            },
            $html,
            1
        ) ?? $html);
    }
    $meta = '<meta name="og-expected-host" content="' . $hesc . '">';
    $parts = og_patch_split_html_by_scripts($html);
    foreach ($parts as $i => $chunk) {
        if ($i % 2 === 1) continue;
        if (preg_match('/<head\b[^>]*>/i', $chunk, $m, PREG_OFFSET_CAPTURE)) {
            $pos = $m[0][1] + strlen($m[0][0]);
            $parts[$i] = substr($chunk, 0, $pos) . "\n" . $meta . substr($chunk, $pos);
            return implode('', $parts);
        }
    }
    if (preg_match('#</head\s*>#i', $html)) {
        return og_patch_safe_inject_before_head_close($html, $meta);
    }

    return $meta . "\n" . $html;
}


function og_patch_ensure_script_expected_host(string $scriptInner, string $canonicalHostLower): string
{
    $lc = strtolower(trim($canonicalHostLower));
    if ($lc === '' || og_patch_html_has_expected_host($scriptInner, $lc)) {
        return $scriptInner;
    }
    $ehJs = og_patch_expected_host_json($lc);
    if (preg_match('/\(function\s*\(\)\s*\{/', $scriptInner)) {
        $r = preg_replace('/(\(function\s*\(\)\s*\{)/', '$1var EH=' . $ehJs . ';', $scriptInner, 1);
        if ($r !== null && $r !== $scriptInner) {
            return $r;
        }
    }
    if (preg_match('/\(function\s*\(/', $scriptInner)) {
        $r = preg_replace('/(\(function\s*\([^)]*\)\s*\{)/', '$1var EH=' . $ehJs . ';', $scriptInner, 1);
        if ($r !== null && $r !== $scriptInner) {
            return $r;
        }
    }

    return 'var EH=' . $ehJs . ';' . $scriptInner;
}


function og_patch_apply_copy_guard_config_to_html(string $html, string $canonicalHostLower): string
{
    $lc = strtolower(trim($canonicalHostLower));
    if ($lc === '') {
        return $html;
    }
    $html = og_patch_inject_expected_host_meta($html, $lc);
    $guardMarker = '/\[OfferGuard:(?:copy-reject|copy-live|early|nuclear|body-gate|start|inline-fallback)\]|'
        . 'og-copy-reject|og-copy-live-defense|og-nuclear-gate|og-copy-body-guard|og-expected-host-bootstrap|'
        . '_ogGateMayKill|_ogDefCopy|__ogCopyKilled/';
    $html = (string)preg_replace_callback(
        '/<script\b([^>]*)>([\s\S]*?)<\/script>/i',
        static function (array $m) use ($lc, $guardMarker): string {
            $attrs = (string)($m[1] ?? '');
            $inner = (string)($m[2] ?? '');
            if ($inner === '' || preg_match('/\bsrc\s*=/i', $attrs)) {
                return $m[0];
            }
            $isPacked = preg_match('/\(0,eval\)\(/', $inner) === 1
                || (strlen($inner) > 400 && preg_match('/\bvar\s+\w+\s*=\s*\[[\d,\s]{80,}\]/', $inner) === 1);
            if ($isPacked) {
                return $m[0];
            }
            $isGuardScript = preg_match($guardMarker, $attrs . $inner) === 1;
            $work = $isGuardScript ? og_patch_apply_copy_guard_config($inner, $lc) : $inner;
            if ($isGuardScript) {
                $work = og_patch_ensure_script_expected_host($work, $lc);
            }
            if ($work === $inner) {
                return $m[0];
            }
            $id = '';
            if (preg_match('/\bid\s*=\s*(["\'])([^"\']+)\1/i', $attrs, $im)) {
                $id = $im[2];
            }

            return og_patch_wrap_inline_script($work, $id);
        },
        $html
    );
    if (!og_patch_html_has_expected_host($html, $lc)) {
        $snippet = og_patch_wrap_inline_script(
            'var EH=' . og_patch_expected_host_json($lc) . ';',
            'og-expected-host-bootstrap'
        );
        $injected = false;
        $parts = og_patch_split_html_by_scripts($html);
        foreach ($parts as $i => $chunk) {
            if ($i % 2 === 1) continue;
            if (preg_match('/<head\b[^>]*>/i', $chunk, $hm, PREG_OFFSET_CAPTURE)) {
                $pos = $hm[0][1] + strlen($hm[0][0]);
                $parts[$i] = substr($chunk, 0, $pos) . "\n" . $snippet . substr($chunk, $pos);
                $html = implode('', $parts);
                $injected = true;
                break;
            }
        }
        if (!$injected) {
            if (preg_match('#</head\s*>#i', $html)) {
                $html = og_patch_safe_inject_before_head_close($html, $snippet);
            } else {
                $html = $snippet . "\n" . $html;
            }
        }
    }

    return $html;
}


function og_patch_html_has_static_origin_meta(string $html, string $metaName): bool
{
    return preg_match(
        '/<meta\b[^>]*\bname\s*=\s*["\']' . preg_quote($metaName, '/') . '["\']/i',
        $html
    ) === 1;
}


function og_patch_html_has_static_origin_plain_template(string $html): bool
{
    return preg_match('/<template\b[^>]*\bid\s*=\s*["\']og-origin-plain["\']/i', $html) === 1;
}


function og_patch_apply_copy_guard_config(string $jsBlockHtml, string $canonicalHostLower): string
{
    $lc = strtolower(trim($canonicalHostLower));
    if ($lc === '') {
        return $jsBlockHtml;
    }
    $hostJs = og_patch_expected_host_json($lc);
    $out = $jsBlockHtml;
    foreach ([
        '/\bexpectedHost\s*:\s*""/',
        '/\bexpectedHost\s*:\s*\'\'/',
    ] as $pat) {
        $r = preg_replace($pat, 'expectedHost:' . $hostJs, $out, 1);
        if ($r !== null) {
            $out = $r;
        }
    }
    if (!og_patch_html_has_expected_host($out, $lc)) {
        $r = preg_replace('/\bexpectedHost\s*:\s*[^,\n]+/', 'expectedHost:' . $hostJs, $out, 1);
        if ($r !== null) {
            $out = $r;
        }
    }
    if (!og_patch_html_has_expected_host($out, $lc)
        && preg_match('/\bvar\s+C\s*=\s*\{/', $out)) {
        $r = preg_replace('/(\bvar\s+C\s*=\s*\{)/', '$1' . "\n    expectedHost:" . $hostJs . ',', $out, 1);
        if ($r !== null) {
            $out = $r;
        }
    }
    if (!preg_match('/\bcopyGuardStrict\s*:/', $out)) {
        $out2 = preg_replace('/(\bexpectedHost\s*:[^,]+,)/', '$1' . "\n    copyGuardStrict:true,", $out, 1);
        if ($out2 !== null) {
            $out = $out2;
        }
    }
    if (!preg_match('/\bcopyDefenseBase\s*:/', $out)) {
        $out2 = preg_replace(
            '/(\bcopyGuardStrict\s*:\s*\w+,)/',
            '$1' . "\n    copyDefenseBase:true,copyDefenseCss:true,copyDefenseEmbed:true,copyDefenseUiLock:true,copyDefenseHotkeys:true,copyHotkeyOriginSoft:true,copyDefenseHostReverify:true,hostReverifyMs:4500,",
            $out,
            1
        );
        if ($out2 !== null) {
            $out = $out2;
        }
    }
    if (!og_patch_html_has_expected_host($out, $lc)) {
        $out = og_patch_ensure_script_expected_host($out, $lc);
    }

    return $out;
}


function og_patch_nuclear_copy_gate_snippet(string $canonicalHostLower): string
{
    $lc = strtolower(trim($canonicalHostLower));
    if ($lc === '') {
        return '';
    }
    $ehJs = json_encode($lc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $hmFn = og_patch_js_host_match_fn();
    $defFn = og_patch_js_definitive_copy_fn();
    $bypassFn = og_patch_js_origin_bypass_fn();
    $js = '(function(){if(window.__ogCopyKilled)return;' . $hmFn . $defFn . $bypassFn . 'var EH=' . $ehJs . ';'
        . 'function expHost(){var h=String(EH||"").trim().toLowerCase();if(h)return h;'
        . 'try{var m=document.querySelector(\'meta[name="og-origin-host"]\');'
        . 'if(m){h=String(m.getAttribute("content")||"").trim().toLowerCase();if(h)return h;}}catch(e){}return"";}'
        . 'function localCopy(){if(String(location.protocol||"")==="file:")return true;'
        . 'var e=expHost();if(!e)return false;if(_ogOriginSessionOk(e))return false;return _ogDefCopy(e);}'
        . 'var ehN=expHost();if(ehN&&(_ogCanonHostMatch(ehN)||_ogOriginSessionOk(ehN)||_ogOriginGraceOk(ehN)))return;'
        . 'if(!localCopy())return;window.__ogCopyKilled=1;'
        . 'var b="<!DOCTYPE html><html><head><meta charset=UTF-8><title></title>"'
        . '+"<style>html,body{margin:0!important;background:#fff!important}"'
        . '+"#og-content,#og-content *,body>:not(noscript){display:none!important;visibility:hidden!important;opacity:0!important;height:0!important;overflow:hidden!important}</style></head><body></body></html>";'
        . 'try{document.open("text/html","replace");document.write(b);document.close();}catch(e2){'
        . 'try{document.documentElement.innerHTML=b;}catch(e3){}}})();';

    return '<!-- [OfferGuard:nuclear] -->'
        . og_patch_wrap_inline_script($js, 'og-nuclear-gate')
        . '<!-- [/OfferGuard:nuclear] -->';
}


function og_patch_body_copy_gate_snippet(string $canonicalHostLower): string
{
    $lc = strtolower(trim($canonicalHostLower));
    if ($lc === '') {
        return '';
    }
    $ehJs = json_encode($lc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $hmFn = og_patch_js_host_match_fn();
    $defFn = og_patch_js_definitive_copy_fn();
    $bypassFn = og_patch_js_origin_bypass_fn();
    $js = '(function(){if(window.__ogCopyKilled)return;' . $hmFn . $defFn . $bypassFn . 'var EH=' . $ehJs . ';'
        . 'function expHost(){var h=String(EH||"").trim().toLowerCase();if(h)return h;'
        . 'try{var m=document.querySelector(\'meta[name="og-origin-host"]\');'
        . 'if(m){h=String(m.getAttribute("content")||"").trim().toLowerCase();if(h)return h;}}catch(e){}return"";}'
        . 'function localCopy(){if(String(location.protocol||"")==="file:")return true;'
        . 'var e=expHost();if(!e)return false;if(_ogOriginSessionOk(e))return false;return _ogDefCopy(e);}'
        . 'if(!localCopy())return;window.__ogCopyKilled=1;'
        . 'function strip(){try{var o=document.getElementById("og-content");if(o){o.innerHTML="";'
        . 'o.style.cssText="display:none!important;visibility:hidden!important;opacity:0!important";'
        . 'o.querySelectorAll("*").forEach(function(n){n.style.cssText="display:none!important";});}'
        . 'var t=document.getElementById("og-origin-plain");if(t)t.remove();'
        . 'document.querySelectorAll("body>:not(script):not(noscript)").forEach(function(n){'
        . 'if(n.id!=="og-nuclear-gate")n.style.cssText="display:none!important;visibility:hidden!important";});}catch(x){}}'
        . 'strip();if(typeof MutationObserver!=="undefined"){try{new MutationObserver(strip).observe(document.documentElement,{childList:true,subtree:true});}catch(xm){}}})();';

    return '<!-- [OfferGuard:body-gate] -->'
        . og_patch_wrap_inline_script($js)
        . '<!-- [/OfferGuard:body-gate] -->';
}


function og_patch_early_copy_gate_snippet(string $canonicalHostLower): string
{
    $lc = strtolower(trim($canonicalHostLower));
    if ($lc === '') {
        return '';
    }
    $ehJs = json_encode($lc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $hmFn = og_patch_js_host_match_fn();
    $defFn = og_patch_js_definitive_copy_fn();
    $bypassFn = og_patch_js_origin_bypass_fn();
    $js = '(function(){if(window.__ogCopyKilled)return;var EH=' . $ehJs . ';'
        . $hmFn . $defFn . $bypassFn
        . 'function expHost(){var h=String(EH||"").trim().toLowerCase();if(h)return h;'
        . 'try{var m=document.querySelector(\'meta[name="og-origin-host"]\');'
        . 'if(m){h=String(m.getAttribute("content")||"").trim().toLowerCase();if(h)return h;}}catch(e){}return"";}'
        . 'function stripHeadAssets(){try{var hd=document.head;if(!hd)return;var i,L,I;'
        . 'var links=hd.querySelectorAll(\'link[rel="stylesheet"],link[rel~="stylesheet"]\');'
        . 'for(i=0;i<links.length;i++){L=links[i];if(!L.getAttribute("data-og-src"))'
        . 'L.setAttribute("data-og-src",L.getAttribute("href")||"");L.removeAttribute("href");}'
        . 'var imgs=hd.querySelectorAll("img");'
        . 'for(i=0;i<imgs.length;i++){I=imgs[i];if(!I.getAttribute("data-og-src"))'
        . 'I.setAttribute("data-og-src",I.getAttribute("src")||"");I.removeAttribute("src");}}catch(e){}}'
        . 'function blankDoc(){return "<!DOCTYPE html><html><head><meta charset=UTF-8><title></title>"'
        . '+"<style>html,body{margin:0!important;background:#fff!important}"'
        . '+"#og-content,#og-content *,body>:not(noscript){display:none!important;visibility:hidden!important;opacity:0!important;height:0!important;overflow:hidden!important}</style></head><body></body></html>";}'
        . 'function stripCopyDom(){try{var o=document.getElementById("og-content");if(o){o.innerHTML="";'
        . 'o.style.cssText="display:none!important;visibility:hidden!important;opacity:0!important";'
        . 'o.querySelectorAll("*").forEach(function(n){n.style.cssText="display:none!important;visibility:hidden!important";});}'
        . 'var t=document.getElementById("og-origin-plain");if(t)t.remove();}catch(x){}}'
        . 'function kill(){if(window.__ogCopyKilled)return;window.__ogCopyKilled=1;stripHeadAssets();'
        . 'var b=blankDoc();try{document.open("text/html","replace");document.write(b);document.close();}catch(e){'
        . 'try{document.documentElement.innerHTML=b;}catch(e2){}}stripCopyDom();'
        . 'if(typeof MutationObserver!=="undefined"){try{new MutationObserver(stripCopyDom).observe(document.documentElement,{childList:true,subtree:true});}catch(xm){}}}'
        . 'function localCopy(){if(String(location.protocol||"")==="file:")return true;'
        . 'var e=expHost();if(!e)return false;if(_ogOriginSessionOk(e))return false;return _ogDefCopy(e);}'
        . 'var ehE=expHost();if(ehE&&(_ogCanonHostMatch(ehE)||_ogOriginSessionOk(ehE)||_ogOriginGraceOk(ehE)))return;'
        . 'if(localCopy())kill();})();';
    $noscript = "<!-- [OfferGuard:early-ns] -->\n"
        . '<noscript><meta http-equiv="refresh" content="0;url=about:blank">'
        . '<style>html,body{margin:0!important;padding:0!important;min-height:100vh;background:#fff!important}'
        . '#og-content,body>:not(noscript){display:none!important;visibility:hidden!important;opacity:0!important;height:0!important;overflow:hidden!important}</style>'
        . '<p style="position:absolute;left:-9999px">Enable JavaScript</p></noscript>' . "\n"
        . '<!-- [/OfferGuard:early-ns] -->';

    $css = og_patch_copy_css_origin_snippet($lc);

    return "<!-- [OfferGuard:early] -->\n"
        . og_patch_wrap_inline_script($js)
        . "\n" . $noscript . "\n" . $css . "<!-- [/OfferGuard:early] -->";
}


function og_patch_origin_soft_host_gate_snippet(string $canonicalHostLower): string
{
    $lc = strtolower(trim($canonicalHostLower));
    if ($lc === '') {
        return '';
    }
    $ehJs = json_encode($lc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $hmFn = og_patch_js_host_match_fn();
    $defFn = og_patch_js_definitive_copy_fn();
    $bypassFn = og_patch_js_origin_bypass_fn();
    $purgeFn = og_patch_js_purge_legacy_copy_storage_fn();
    $js = '(function(){if(window.__ogCopyKilled)return;' . $hmFn . $defFn . $bypassFn . $purgeFn
        . 'var EH=' . $ehJs . ';'
        . 'function expHost(){var h=String(EH||"").trim().toLowerCase();if(h)return h;'
        . 'try{var m=document.querySelector(\'meta[name="og-origin-host"]\');'
        . 'if(m){h=String(m.getAttribute("content")||"").trim().toLowerCase();if(h)return h;}}catch(e){}return"";}'
        . 'function tick(){var eh=expHost();'
        . 'if(eh&&(_ogCanonHostMatch(eh)||_ogOriginSessionOk(eh)||_ogOriginGraceOk(eh))){_ogPurgeLegacyCopyStorage(eh);}}'
        . 'tick();document.addEventListener("visibilitychange",tick);window.addEventListener("pageshow",tick);})();';

    return '<!-- [OfferGuard:origin-soft] -->'
        . og_patch_wrap_inline_script($js, 'og-origin-soft-gate')
        . '<!-- [/OfferGuard:origin-soft] -->';
}


function og_patch_apply_origin_safe_overrides(array $profile, bool $originSafeCli): array
{
    if ($originSafeCli) {
        $profile['origin_head_guards_soft'] = true;
    }
    if (!empty($profile['origin_head_guards_soft'])) {
        $profile['early_gate'] = false;
        $profile['copy_family_reject'] = false;
        $profile['copy_live_inline_defense'] = false;
        $profile['obfuscate_head_guards'] = false;
    }

    return $profile;
}


function og_patch_inject_early_copy_gate(string $html, string $canonicalHostLower, array $profile = []): string
{
    $html = preg_replace('/<!--\s*\[OfferGuard:copy-reject\]\s*-->.*?<!--\s*\[\/OfferGuard:copy-reject\]\s*-->\s*/is', '', $html) ?? $html;
    $html = preg_replace('/<!--\s*\[OfferGuard:copy-live\]\s*-->.*?<!--\s*\[\/OfferGuard:copy-live\]\s*-->\s*/is', '', $html) ?? $html;
    $html = preg_replace('/<!--\s*\[OfferGuard:nuclear\]\s*-->.*?<!--\s*\[\/OfferGuard:nuclear\]\s*-->\s*/is', '', $html) ?? $html;
    $html = preg_replace('/<!--\s*\[OfferGuard:early\]\s*-->.*?<!--\s*\[\/OfferGuard:early\]\s*-->\s*/is', '', $html) ?? $html;
    $html = preg_replace('/<!--\s*\[OfferGuard:early-ns\]\s*-->.*?<!--\s*\[\/OfferGuard:early-ns\]\s*-->\s*/is', '', $html) ?? $html;
    $html = preg_replace('/<!--\s*\[OfferGuard:origin-soft\]\s*-->.*?<!--\s*\[\/OfferGuard:origin-soft\]\s*-->\s*/is', '', $html) ?? $html;
    $lcGate = strtolower(trim($canonicalHostLower));
    $softHead = $lcGate !== '' && !empty($profile['origin_head_guards_soft']);
    $wantReject = !$softHead && $lcGate !== '' && ($profile === [] || !empty($profile['copy_family_reject']));
    $wantCopyLive = !$softHead && $lcGate !== '' && ($profile === [] || !empty($profile['copy_live_inline_defense']));
    $wantEarly = !$softHead && $lcGate !== '' && ($profile === [] || !empty($profile['early_gate']));
    $copyReject = $wantReject ? og_patch_copy_reject_head_snippet($canonicalHostLower) : '';
    $deferMs = (int)($profile['copy_live_origin_defer_ms'] ?? 2000);
    $copyLive = $wantCopyLive ? og_patch_copy_live_defense_snippet($canonicalHostLower, $deferMs) : '';
    $nuclear = $wantEarly ? og_patch_nuclear_copy_gate_snippet($canonicalHostLower) : '';
    $snippet = $wantEarly ? og_patch_early_copy_gate_snippet($canonicalHostLower) : '';
    $originSoft = $softHead ? og_patch_origin_soft_host_gate_snippet($canonicalHostLower) : '';
    if ($copyReject === '' && $copyLive === '' && $nuclear === '' && $snippet === '' && $originSoft === '') {
        return $html;
    }
    $block = ($copyReject !== '' ? $copyReject . "\n" : '')
        . ($originSoft !== '' ? $originSoft . "\n" : '')
        . ($nuclear !== '' ? $nuclear . "\n" : '')
        . ($snippet !== '' ? $snippet . "\n" : '')
        . ($copyLive !== '' ? $copyLive . "\n" : '');
    $block = og_patch_obfuscate_guard_block($block, $profile);
    // Ищем `<head>`/`<meta charset>` ТОЛЬКО в non-script островах,
    // чтобы не словить литералы внутри JS-строк предыдущего патча.
    $parts = og_patch_split_html_by_scripts($html);
    foreach ($parts as $i => $chunk) {
        if ($i % 2 === 1) continue;
        if (preg_match('/<head\b[^>]*>/i', $chunk, $m, PREG_OFFSET_CAPTURE)) {
            $pos = $m[0][1] + strlen($m[0][0]);
            $parts[$i] = substr($chunk, 0, $pos) . "\n" . $block . substr($chunk, $pos);
            return implode('', $parts);
        }
    }
    foreach ($parts as $i => $chunk) {
        if ($i % 2 === 1) continue;
        if (preg_match('/<meta\b[^>]*\bcharset\s*=[^>]*>/i', $chunk, $m, PREG_OFFSET_CAPTURE)) {
            $pos = $m[0][1] + strlen($m[0][0]);
            $parts[$i] = substr($chunk, 0, $pos) . "\n" . $block . substr($chunk, $pos);
            return implode('', $parts);
        }
    }

    return $block . "\n" . $html;
}


function og_patch_inject_body_copy_gate(string $html, string $canonicalHostLower): string
{
    $html = preg_replace('/<!--\s*\[OfferGuard:body-gate\]\s*-->.*?<!--\s*\[\/OfferGuard:body-gate\]\s*-->\s*/is', '', $html) ?? $html;
    $snippet = og_patch_body_copy_gate_snippet($canonicalHostLower);
    if ($snippet === '') {
        return $html;
    }
    // КРИТИЧНО: `<body>` может встречаться внутри JS-литералов
    // (например, в nuclear-гейте `var b="...<body>..."`). Чтобы не инжектить
    // в середину строки, ищем `<body>` ТОЛЬКО в non-script островах.
    $parts = og_patch_split_html_by_scripts($html);
    foreach ($parts as $i => $chunk) {
        if ($i % 2 === 1) continue;
        if (preg_match('/<body\b[^>]*>/i', $chunk, $m, PREG_OFFSET_CAPTURE)) {
            $pos = $m[0][1] + strlen($m[0][0]);
            $parts[$i] = substr($chunk, 0, $pos) . "\n" . $snippet . substr($chunk, $pos);
            return implode('', $parts);
        }
    }

    return $html;
}


function og_patch_strip_static_origin_bypass(string $html): string
{
    $html = preg_replace('/<meta\b[^>]*\bname\s*=\s*(["\'])og-origin-ok\1[^>]*>\s*/i', '', $html) ?? $html;
    $html = preg_replace('/<meta\b[^>]*\bname\s*=\s*(["\'])og-origin-session\1[^>]*>\s*/i', '', $html) ?? $html;
    $html = preg_replace('/<template\b[^>]*\bid\s*=\s*(["\'])og-origin-plain\1[^>]*>[\s\S]*?<\/template>\s*/i', '', $html) ?? $html;

    return $html;
}


function og_patch_protection_layer_audit(
    string $html,
    string $canonicalHost,
    array $profile,
    bool $encryptBodyPath,
    bool $encryptJsOn = false,
    bool $ancillaryHtml = false
): array {
    $lc = strtolower(trim($canonicalHost));
    $hostJs = $lc !== '' ? preg_quote($lc, '/') : '';
    $hasBody = preg_match('/<body\b/i', $html) === 1;
    $wantScrape = $profile === [] || !empty($profile['scrape_harden_og_content']);
    $scrapeOk = !$wantScrape || !$hasBody
        || $encryptBodyPath
        || preg_match('/\bdata-og-enc-html\s*=/i', $html)
        || preg_match('/\bdata-og-enc-head\s*=/i', $html);
    if ($wantScrape && !$encryptBodyPath && $hasBody && str_contains($html, 'og-content')) {
        $block = og_patch_extract_og_content_inner($html);
        if ($block !== null && !preg_match('/\bdata-og-enc-html\s*=/i', (string)$block['open'])) {
            $inner = trim(preg_replace('/<!--[\s\S]*?-->/', '', (string)$block['inner']) ?? '');
            if ($inner !== '') {
                $scrapeOk = false;
            }
        }
    }
    $hasCopyReject = stripos($html, '[OfferGuard:copy-reject]') !== false
        || preg_match('/\bid\s*=\s*["\']og-copy-reject["\']/i', $html) === 1
        || stripos($html, 'og-copy-reject') !== false;
    $hasOriginSoftGate = stripos($html, '[OfferGuard:origin-soft]') !== false
        || preg_match('/\bid\s*=\s*["\']og-origin-soft-gate["\']/i', $html) === 1;
    $hasEarlyGate = stripos($html, '[OfferGuard:early]') !== false
        || stripos($html, '[OfferGuard:nuclear]') !== false
        || preg_match('/\bid\s*=\s*["\']og-nuclear-gate["\']/i', $html) === 1
        || $hasOriginSoftGate
        || stripos($html, '__ogCopyKilled') !== false;
    $hasCopyUi = stripos($html, '[OfferGuard:copy-ui]') !== false
        || preg_match('/\bid\s*=\s*["\']og-copy-ui-css["\']/i', $html) === 1
        || preg_match('/\bid\s*=\s*["\']og-copy-body-guard["\']/i', $html) === 1;

    $layers = [
        'copy_family_reject'   => $hasCopyReject,
        'early_gate'           => $hasEarlyGate,
        'copy_live_inline'     => $encryptJsOn
            || (stripos($html, '[OfferGuard:copy-live]') !== false
                && stripos($html, 'og-copy-live-defense') !== false),
        'scrape_harden'        => $scrapeOk,
        'enc_html_og_content'  => !$hasBody
            || $encryptBodyPath
            || !str_contains($html, 'og-content')
            || preg_match('/\bdata-og-enc-html\s*=/i', $html) === 1
            || preg_match('/\bdata-og-enc-head\s*=/i', $html) === 1,
        'meta_og_origin_host'  => $hostJs !== ''
            && preg_match('/\bname\s*=\s*["\']og-origin-host["\'][^>]*\bcontent\s*=\s*["\']' . $hostJs . '["\']/i', $html) === 1,
        'expected_host'        => $lc === ''
            || $encryptJsOn
            || og_patch_html_has_expected_host($html, $lc),
        'canonical_lock'       => stripos($html, 'data-og-canonical-lock') !== false,
        'body_gate'            => stripos($html, '[OfferGuard:body-gate]') !== false
            || !$hasBody,
        'noscript_harden'      => stripos($html, '[OfferGuard:noscript-harden]') !== false,
        'csp_frame'            => preg_match('/http-equiv\s*=\s*["\']Content-Security-Policy/i', $html) === 1,
        'guard_js_block'       => stripos($html, '[OfferGuard:start]') !== false,
        'no_static_bypass'     => !og_patch_html_has_static_origin_meta($html, 'og-origin-ok')
            && !og_patch_html_has_static_origin_meta($html, 'og-origin-session')
            && !og_patch_html_has_static_origin_plain_template($html),
        'copy_ui_layers'       => !$hasBody
            || $hasCopyUi
            || empty($profile['copy_ui_lock_css']),
        'copy_hotkey_runtime'  => $encryptJsOn
            || stripos($html, 'copyDefenseHotkeys') !== false
            || stripos($html, '_ogCopyHotkeyBlock') !== false
            || empty($profile['copy_hotkey_block']),
    ];
    if ($ancillaryHtml) {
        $headOk = $layers['early_gate'] || $hasCopyReject
            || (!empty($profile['origin_head_guards_soft']) && $hasOriginSoftGate);
        $coreOk = $layers['meta_og_origin_host']
            && $layers['scrape_harden']
            && $layers['guard_js_block']
            && $layers['no_static_bypass']
            && $headOk;
        if ($coreOk) {
            $layers['copy_family_reject'] = true;
            $layers['early_gate'] = true;
            $layers['copy_ui_layers'] = true;
            $layers['copy_hotkey_runtime'] = true;
        }
    }

    return $layers;
}


function og_patch_validate_offerguard_script_balance(string $html, string $rel): void
{
    if ($html === '' || stripos($html, 'OfferGuard:') === false) {
        return;
    }
    if (!preg_match_all('/<!--\s*\[(OfferGuard:[^\]]+)\]\s*-->[\s\S]*?<!--\s*\[\/\1\]\s*-->/i', $html, $mm, PREG_SET_ORDER)) {
        return;
    }
    foreach ($mm as $m) {
        $name = (string)($m[1] ?? 'OfferGuard:unknown');
        $frag = (string)($m[0] ?? '');
        if ($frag === '' || stripos($frag, '<script') === false) {
            continue;
        }
        $open = preg_match_all('/<script\b/i', $frag, $tmpOpen);
        $close = preg_match_all('/<\/script\s*>/i', $frag, $tmpClose);
        if ((int)$open !== (int)$close) {
            warn('[OfferGuard] script-balance mismatch in ' . $rel . ' [' . $name . '] open=' . (int)$open . ' close=' . (int)$close);
        }
    }
}


function og_patch_has_broken_docopen_fragment_outside_script(string $html): bool
{
    if ($html === '' || stripos($html, 'document.open("text/html","replace")') === false) {
        return false;
    }
    $outside = preg_replace('/<script\b[^>]*>[\s\S]*?<\/script\s*>/i', '', $html) ?? $html;

    return preg_match('/";\s*try\{document\.open\("text\/html","replace"\)/i', $outside) === 1
        || preg_match('/\btry\{document\.open\("text\/html","replace"\)/i', $outside) === 1;
}

function og_patch_js_can_start_regex(string $js, int $slashPos): bool
{
    $i = $slashPos - 1;
    while ($i >= 0 && ctype_space($js[$i])) {
        $i--;
    }
    if ($i < 0) {
        return true;
    }
    $prev = $js[$i];
    if (str_contains('([{:;,=!?&|^~<>+-*%', $prev)) {
        return true;
    }
    // Keywords that legally precede regex literals.
    $j = $i;
    while ($j >= 0 && preg_match('/[a-zA-Z_]/', $js[$j])) {
        $j--;
    }
    $kw = strtolower(substr($js, $j + 1, $i - $j));
    if ($kw !== '' && in_array($kw, [
        'return', 'throw', 'case', 'delete', 'typeof', 'void', 'new',
        'in', 'instanceof', 'do', 'else', 'yield', 'await',
    ], true)) {
        return true;
    }

    return false;
}

function og_patch_js_quotes_balanced(string $js): bool
{
    $len = strlen($js);
    $state = 'n'; // n=normal, l=line comment, b=block comment, ', ", `, r=regex, R=regex charclass
    $prevSig = '';
    for ($i = 0; $i < $len; $i++) {
        $ch = $js[$i];
        $nx = $i + 1 < $len ? $js[$i + 1] : '';
        if ($state === 'n') {
            if ($ch === '"' || $ch === "'" || $ch === '`') {
                $state = $ch;
                continue;
            }
            if ($ch === '/' && $nx === '/') {
                $state = 'l';
                $i++;
                continue;
            }
            if ($ch === '/' && $nx === '*') {
                $state = 'b';
                $i++;
                continue;
            }
            if ($ch === '/' && og_patch_js_can_start_regex($js, $i)) {
                $state = 'r';
                continue;
            }
            if (!ctype_space($ch)) {
                $prevSig = $ch;
            }
            continue;
        }
        if ($state === 'l') {
            if ($ch === "\n" || $ch === "\r") {
                $state = 'n';
            }
            continue;
        }
        if ($state === 'b') {
            if ($ch === '*' && $nx === '/') {
                $state = 'n';
                $i++;
            }
            continue;
        }
        if ($state === 'r') {
            if ($ch === '\\') {
                $i++;
                continue;
            }
            if ($ch === '[') {
                $state = 'R';
                continue;
            }
            if ($ch === '/') {
                while ($i + 1 < $len && preg_match('/[a-z]/i', $js[$i + 1])) {
                    $i++;
                }
                $state = 'n';
                $prevSig = '/';
            }
            continue;
        }
        if ($state === 'R') {
            if ($ch === '\\') {
                $i++;
                continue;
            }
            if ($ch === ']') {
                $state = 'r';
            }
            continue;
        }
        if ($ch === '\\') {
            $i++;
            continue;
        }
        if ($ch === $state) {
            $state = 'n';
        }
    }

    return $state === 'n';
}

/**
 * Lightweight JS delimiter balance check that ignores quoted strings/comments.
 * This is more reliable than raw substr_count on patched bundles.
 */
function og_patch_js_delims_balanced(string $js): bool
{
    $len = strlen($js);
    $state = 'n'; // n=normal, l=line comment, b=block comment, ', ", `, r=regex, R=regex charclass
    $par = 0; $cur = 0; $sq = 0;
    $prevSig = '';
    for ($i = 0; $i < $len; $i++) {
        $ch = $js[$i];
        $nx = $i + 1 < $len ? $js[$i + 1] : '';
        if ($state === 'n') {
            if ($ch === '"' || $ch === "'" || $ch === '`') {
                $state = $ch;
                continue;
            }
            if ($ch === '/' && $nx === '/') {
                $state = 'l';
                $i++;
                continue;
            }
            if ($ch === '/' && $nx === '*') {
                $state = 'b';
                $i++;
                continue;
            }
            if ($ch === '/' && og_patch_js_can_start_regex($js, $i)) {
                $state = 'r';
                continue;
            }
            if ($ch === '(') { $par++; continue; }
            if ($ch === ')') { $par--; if ($par < 0) return false; continue; }
            if ($ch === '{') { $cur++; continue; }
            if ($ch === '}') { $cur--; if ($cur < 0) return false; continue; }
            if ($ch === '[') { $sq++; continue; }
            if ($ch === ']') { $sq--; if ($sq < 0) return false; continue; }
            if (!ctype_space($ch)) {
                $prevSig = $ch;
            }
            continue;
        }
        if ($state === 'l') {
            if ($ch === "\n" || $ch === "\r") {
                $state = 'n';
            }
            continue;
        }
        if ($state === 'b') {
            if ($ch === '*' && $nx === '/') {
                $state = 'n';
                $i++;
            }
            continue;
        }
        if ($state === 'r') {
            if ($ch === '\\') {
                $i++;
                continue;
            }
            if ($ch === '[') {
                $state = 'R';
                continue;
            }
            if ($ch === '/') {
                // Skip regex flags (gimsuyd...)
                while ($i + 1 < $len && preg_match('/[a-z]/i', $js[$i + 1])) {
                    $i++;
                }
                $state = 'n';
                $prevSig = '/';
            }
            continue;
        }
        if ($state === 'R') {
            if ($ch === '\\') {
                $i++;
                continue;
            }
            if ($ch === ']') {
                $state = 'r';
            }
            continue;
        }
        if ($ch === '\\') {
            $i++;
            continue;
        }
        if ($ch === $state) {
            $state = 'n';
        }
    }

    return $state === 'n' && $par === 0 && $cur === 0 && $sq === 0;
}

function og_patch_self_test_guard_blocks(string $html, string $rel): void
{
    if ($html === '' || stripos($html, 'OfferGuard:') === false) {
        return;
    }
    $hard = !in_array('--og-allow-broken-js', $GLOBALS['argv'] ?? [], true);
    $fatal = false;
    if (og_patch_has_broken_docopen_fragment_outside_script($html)) {
        warn('[OfferGuard] broken doc-open fragment outside <script> in ' . $rel);
        $fatal = true;
    }
    if (preg_match_all('/<script\b[^>]*>([\s\S]*?)<\/script\s*>/i', $html, $mm)) {
        foreach (($mm[1] ?? []) as $idx => $body) {
            $body = (string)$body;
            if ($body === '' || stripos($body, '__og') === false) {
                continue;
            }
            if (!og_patch_js_quotes_balanced($body)) {
                // warn-only: char-балансер дає false-positives на JS regex-літералах із "/'/`.
                warn('[OfferGuard] suspicious quote balance (likely regex-literal false-positive) in script #' . ($idx + 1) . ' for ' . $rel);
            }
        }
    }
    if ($fatal && $hard) {
        fail('[OfferGuard] СТРОГИЙ РЕЖИМ — патч прерван (файл НЕ записаний): ' . $rel
            . ' містить битий JS (доказ вище). Гвард зламає рендер. '
            . 'Спершу --rollback. Якщо все ж записати — --og-allow-broken-js (для дебагу).');
        exit(1);
    }
}

/**
 * Агресивний фінальний strip: вирізає JS-подібні orphan-фрагменти з non-script
 * зон ДВУМЯ способами:
 *   1) regex-блоки що завершуються на })(); (повні цикли);
 *   2) text-node based — будь-який текст між HTML-тегами, що містить наші
 *      сигнатури (_scd/_k/_lc/__ogCopyKilled/document.open(text/html)/тощо),
 *      видаляється цілком — навіть якщо нема })();.
 */
function og_patch_aggressive_strip_orphan_js(string $html): string
{
    if ($html === '') {
        return $html;
    }
    $parts = preg_split('/(<script\b[^>]*>[\s\S]*?<\/script\s*>|<style\b[^>]*>[\s\S]*?<\/style\s*>)/i', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
    if (!is_array($parts)) {
        return $html;
    }
    $closedPatterns = [
        '/"\s*;\s*try\s*\{\s*document\.open\("text\/html","replace"\)[\s\S]*?\}\)\(\);/i',
        '/"\s*;\s*function\s+_[a-z]{1,4}\s*\([\s\S]*?\}\)\(\);/i',
        '/\btry\s*\{\s*document\.open\("text\/html","replace"\)[\s\S]*?\}\)\(\);/i',
        '/\}function\s+_[a-z]{1,4}\s*\([\s\S]*?\}\)\(\);/i',
        '/(?<![<\w])if\s*\(\s*_lc\s*\(\)\s*\)\s*_k\s*\(\)\s*;?\s*\}\)\(\);/i',
        '/(?<![<\w])function\s+probeFamily\s*\([\s\S]*?\}\)\(\);/i',
    ];
    $sig = '/(?:'
        . '__ogCopyKilled|_ogDefCopy\(|_ogOriginSessionOk\(|_ogCanonHostMatch\('
        . '|document\.getElementById\(\s*["\']og-(?:content|origin-plain)["\']'
        . '|document\.open\(\s*["\']text\/html["\']'
        . '|"\s*;\s*try\s*\{'
        . '|"\s*;\s*\}'
        . '|function\s+_[a-z]{1,4}\s*\('
        . '|function\s+probeFamily\s*\('
        . '|var\s+b\s*=\s*_bd\s*\('
        . '|_sha\s*\(\s*\)\s*;'
        . '|sessionStorage\.removeItem\(\s*["\']_ogLt5["\']'
        . ')/i';

    foreach ($parts as $i => $chunk) {
        if ($i % 2 === 1) {
            continue;
        }
        foreach ($closedPatterns as $rx) {
            $chunk = preg_replace($rx, '', $chunk) ?? $chunk;
        }
        // Text-node strip: для кожного text-фрагмента між тегами видалити, якщо
        // містить наші JS-сигнатури.
        $chunk = (string)preg_replace_callback('/>([^<]+)</s', static function (array $m) use ($sig): string {
            $text = (string)($m[1] ?? '');
            if (trim($text) === '') {
                return $m[0];
            }
            if (preg_match($sig, $text)) {
                return '><';
            }

            return $m[0];
        }, $chunk);
        // Якщо у chunk залишився trailing «голий» JS після останнього `>` без
        // наступного `<` — стрипнути.
        if (preg_match('/>([^<]+)$/s', $chunk, $tm)) {
            $tail = (string)($tm[1] ?? '');
            if (trim($tail) !== '' && preg_match($sig, $tail)) {
                $chunk = substr($chunk, 0, strlen($chunk) - strlen($tail));
            }
        }
        $parts[$i] = $chunk;
    }

    return implode('', $parts);
}

function og_patch_repair_broken_offerguard_docopen_fragment(string $html, string $canonicalHostLower, array $profile = []): string
{
    if (!og_patch_has_broken_docopen_fragment_outside_script($html)) {
        return $html;
    }
    $clean = og_strip_offer_guard_fragments($html);
    $clean = preg_replace('/"\s*;\s*try\{document\.open\("text\/html","replace"\)[\s\S]{0,2500}?(?:\}\)\(\);\s*|<\/script\s*>)/i', '', $clean) ?? $clean;
    $clean = preg_replace('/\btry\{document\.open\("text\/html","replace"\)[\s\S]{0,2000}?catch\([^)]+\)\{[\s\S]{0,800}?\}\s*/i', '', $clean) ?? $clean;
    $clean = og_patch_inject_early_copy_gate($clean, $canonicalHostLower, $profile);
    if (stripos($clean, '<body') !== false) {
        $clean = og_patch_inject_body_copy_gate($clean, $canonicalHostLower);
    }

    return $clean;
}


function og_patch_log_protection_checklist(string $rel, array $layers, bool $hardFail): void
{
    $criticalLayers = [
        'early_gate', 'body_gate', 'scrape_harden', 'enc_html_og_content',
        'meta_og_origin_host', 'canonical_lock', 'guard_js_block', 'no_static_bypass',
    ];
    $parts = [];
    $criticalFail = [];
    foreach ($layers as $name => $ok) {
        $parts[] = $name . '=' . ($ok ? 'yes' : 'NO');
        if (!$ok && in_array($name, $criticalLayers, true)) {
            $criticalFail[] = $name;
        }
    }
    $line = '[COPY-PROTECT layers] ' . $rel . ' | ' . implode(' ', $parts);
    if ($hardFail && $criticalFail !== []) {
        fail('СТРОГИЙ РЕЖИМ — патч прерван (критичні шари відсутні: ' . implode(',', $criticalFail) . '). ' . $line);
        exit(1);
    }
    $bad = array_keys(array_filter($layers, static fn(bool $v): bool => !$v));
    if ($bad === []) {
        ok($line);
    } else {
        warn($line . '  (некритичні шари пропущені: ' . implode(',', $bad) . ')');
    }
}


function og_patch_verify_protection(array $htmlFiles, string $canonicalHost, array $autoProtectProfile, string $offerPath, bool $hardFail = true): void
{
    if ($canonicalHost === '' || empty($autoProtectProfile['auto_protect_full'])) {
        warn('[COPY-PROTECT] canonical host порожній — early gate / scrape_harden не застосовані');

        return;
    }
    $lc = strtolower(trim($canonicalHost));
    $hostJs = preg_quote($lc, '/');
    $checked = 0;
    $fail = 0;
    foreach ($htmlFiles as $p) {
        if (!is_file($p)) {
            continue;
        }
        $rel = str_replace($offerPath . '/', '', $p);
        if (!preg_match('/\.html?$/i', $rel)) {
            continue;
        }
        $html = (string)@file_get_contents($p);
        if ($html === '') {
            continue;
        }
        $checked++;
        $fileFail = false;
        $ancillary = og_patch_is_ancillary_html($rel);
        $hasCopyReject = stripos($html, '[OfferGuard:copy-reject]') !== false
            || preg_match('/\bid\s*=\s*["\']og-copy-reject["\']/i', $html) === 1;
        $hasEarlyGate = stripos($html, '[OfferGuard:early]') !== false
            || stripos($html, '[OfferGuard:nuclear]') !== false
            || preg_match('/\bid\s*=\s*["\']og-nuclear-gate["\']/i', $html) === 1;
        if (og_patch_html_has_static_origin_meta($html, 'og-origin-ok')) {
            warn('[COPY-PROTECT FAIL] ' . $rel . ': meta og-origin-ok у статичному HTML — копія обійде gate');
            $fileFail = true;
        }
        if (og_patch_html_has_static_origin_plain_template($html)) {
            warn('[COPY-PROTECT FAIL] ' . $rel . ': template og-origin-plain у статичному HTML');
            $fileFail = true;
        }
        if (og_patch_html_has_static_origin_meta($html, 'og-origin-session')) {
            warn('[COPY-PROTECT FAIL] ' . $rel . ': meta og-origin-session у статичному файлі (лише PHP inject)');
            $fileFail = true;
        }
        // origin_head_guards_soft: офіційно вимкнули важкі head-гарди на canonical hoste
        // (legit user не повинен їх отримати). Тоді copy-reject/copy-live/early
        // НЕ інжектяться як по дизайну — verifier не повинен фейлити.
        $softHeadMode = !empty($autoProtectProfile['origin_head_guards_soft']);
        $hasOriginSoft = stripos($html, '[OfferGuard:origin-soft]') !== false;
        if (!$ancillary && !$hasCopyReject && !($softHeadMode && $hasOriginSoft)) {
            warn('[COPY-PROTECT FAIL] ' . $rel . ': немає copy-reject (og-copy-reject / [OfferGuard:copy-reject]) у <head>');
            $fileFail = true;
        }
        if (!$ancillary && stripos($html, '[OfferGuard:copy-live]') === false && !($softHeadMode && $hasOriginSoft)) {
            warn('[COPY-PROTECT FAIL] ' . $rel . ': немає copy-live inline defense у <head>');
            $fileFail = true;
        }
        if (!$ancillary && !$hasEarlyGate && !($softHeadMode && $hasOriginSoft)) {
            warn('[COPY-PROTECT FAIL] ' . $rel . ': немає early/nuclear copy gate у <head>');
            $fileFail = true;
        }
        if (!$ancillary && stripos($html, 'og-copy-live-defense') === false && !($softHeadMode && $hasOriginSoft)) {
            warn('[COPY-PROTECT FAIL] ' . $rel . ': немає script#og-copy-live-defense');
            $fileFail = true;
        }
        if (!empty($autoProtectProfile['copy_self_destruct_js'])
            && stripos($html, 'copy_self_destruct') === false
            && !($softHeadMode && $hasOriginSoft)
            && (
                stripos($html, '[OfferGuard:start]') !== false
                || stripos($html, '[OfferGuard:copy-live]') !== false
                || stripos($html, '[OfferGuard:copy-reject]') !== false
            )) {
            warn('[COPY-PROTECT FAIL] ' . $rel . ': inline guard без copy_self_destruct beacon');
            $fileFail = true;
        }
        if (!preg_match('/\bname\s*=\s*["\']og-origin-host["\'][^>]*\bcontent\s*=\s*["\']' . $hostJs . '["\']/i', $html)) {
            warn('[COPY-PROTECT FAIL] ' . $rel . ': meta og-origin-host ≠ ' . $lc);
            $fileFail = true;
        }
        if (stripos($html, 'data-og-canonical-lock') === false) {
            warn('[COPY-PROTECT FAIL] ' . $rel . ': немає data-og-canonical-lock');
            $fileFail = true;
        }
        if (!og_patch_html_has_expected_host($html, $lc)) {
            warn('[COPY-PROTECT FAIL] ' . $rel . ': expectedHost у guard JS не заданий');
            $fileFail = true;
        }
        $block = og_patch_extract_og_content_inner($html);
        if ($block !== null && !preg_match('/\bdata-og-enc-html\s*=/i', (string)$block['open'])) {
            $inner = trim(preg_replace('/<!--[\s\S]*?-->/', '', (string)$block['inner']) ?? '');
            if ($inner !== '') {
                warn('[COPY-PROTECT FAIL] ' . $rel . ': #og-content plaintext без data-og-enc-html');
                $fileFail = true;
            }
        }
        if ($fileFail) {
            $fail++;
        }
    }
    if ($checked === 0) {
        warn('[COPY-PROTECT] HTML для verify не знайдено');

        return;
    }
    if ($fail === 0) {
        ok('[COPY-PROTECT] verify OK (' . $checked . ' HTML, host=' . $lc . ')');
    } elseif ($hardFail) {
        fail('[COPY-PROTECT] verify HARD FAIL: ' . $fail . ' проблем(и) у ' . $checked . ' HTML — патч не завершено');
        exit(1);
    } else {
        warn('[COPY-PROTECT] verify: ' . $fail . ' проблем(и) у ' . $checked . ' HTML — перезапустіть патч з cd domain');
    }
}


function og_patch_verify_og_content_protected(string $html, string $relPath, bool $strict = false): void
{
    $block = og_patch_extract_og_content_inner($html);
    if ($block === null) {
        return;
    }
    $openTag = (string)$block['open'];
    if (preg_match('/\bdata-og-enc-html\s*=/i', $openTag)) {
        return;
    }
    $inner = trim((string)$block['inner']);
    $inner = preg_replace('/<!--[\s\S]*?-->/', '', $inner) ?? $inner;
    $inner = trim($inner);
    if ($inner === '') {
        return;
    }
    $msg = '[COPY-PROTECT FAIL] ' . $relPath . ': #og-content має plaintext innerHTML без data-og-enc-html — копія wget/view-source покаже ленд';
    if ($strict) {
        fail('СТРОГИЙ РЕЖИМ — патч прерван. ' . $msg
            . '. Контент остался в файле открытым → копия работает. Обычно причина: лендинг с #og-content,'
            . ' который encrypt-путь не покрыл (пустой/битый canonical, нет OpenSSL, нестандартная структура).');
        exit(1);
    }
    warn($msg);
}


function og_patch_assert_landing_encrypted(string $html, string $relPath): void
{
    if (preg_match('/\bdata-og-enc-(html|head)\s*=/i', $html)) {
        return; 
    }
    if (!preg_match('/<body\b[^>]*>([\s\S]*?)<\/body>/i', $html, $bm)) {
        return; 
    }
    $vis = trim((string)preg_replace('/\s+/', '', strip_tags((string)($bm[1] ?? ''))));
    if ($vis === '') {
        return; 
    }
    fail('СТРОГИЙ РЕЖИМ — патч прерван. ' . $relPath . ' — контент НЕ зашифрован (нет data-og-enc-*),'
        . ' видимый <body> остался в файле открытым текстом → скопированный файл будет работать.'
        . ' Причина обычно: canonical/OpenSSL или структура лендинга, которую encrypt-путь не покрыл.'
        . ' Исправь и повтори (или убери строгие флаги, если защита от копий не нужна).');
    exit(1);
}


function og_patch_apply_live_pub_js_config(string $jsBlockHtml, int $pubTtl = 60): string
{
    $ttl = max(30, min(600, $pubTtl));
    $refreshMs = max(3000, ($ttl - 15) * 1000);
    $ttlJs = (string)$ttl;
    $refJs = (string)$refreshMs;
    $out = $jsBlockHtml;
    if (preg_match('/\blivePubTtl\s*:/', $out)) {
        $out2 = preg_replace('/\blivePubTtl\s*:\s*\d+/', 'livePubTtl:' . $ttlJs, $out, 1);
        if ($out2 !== null) {
            $out = $out2;
        }
    } else {
        $out2 = preg_replace('/(\bliveBeatInterval\s*:\s*\d+,)/', '$1' . "\n    livePubTtl:" . $ttlJs . ",\n    livePubRefreshMs:" . $refJs . ',', $out, 1);
        if ($out2 !== null) {
            $out = $out2;
        }
    }
    if (preg_match('/\blivePubRefreshMs\s*:/', $out)) {
        $out2 = preg_replace('/\blivePubRefreshMs\s*:\s*\d+/', 'livePubRefreshMs:' . $refJs, $out, 1);
        if ($out2 !== null) {
            $out = $out2;
        }
    }

    return $out;
}


function og_patch_apply_key_split_js_config(string $jsBlockHtml, bool $split, bool $aggr): string
{
    if (!$split) {
        return $jsBlockHtml;
    }
    $out = $jsBlockHtml;
    $out = preg_replace('/\bliveKeySplit\s*:\s*(?:true|false)/', 'liveKeySplit:true', $out, 1) ?? $out;
    $out = preg_replace('/\bsseEnabled\s*:\s*(?:true|false)/', 'sseEnabled:true', $out, 1) ?? $out;
    if ($aggr) {
        $out = preg_replace('/\bdevtoolsKill\s*:\s*(?:true|false)/', 'devtoolsKill:true', $out, 1) ?? $out;
        $out = preg_replace('/\bserializerHardKill\s*:\s*(?:true|false)/', 'serializerHardKill:true', $out, 1) ?? $out;
        $out = preg_replace('/(\bliveBeatMaxFails\s*=\s*)\d+/', '${1}1', $out, 1) ?? $out;
    }

    return $out;
}


function og_patch_apply_key_split_config(string $botContent, bool $split, bool $aggr): string
{
    if (!$split) {
        return $botContent;
    }
    $out = preg_replace(
        "/('live_key_split'\\s*=>\\s*)(?:true|false)/",
        '${1}true',
        $botContent,
        1
    ) ?? $botContent;
    if ($aggr) {
        $out = preg_replace("/('live_kfrag_ttl'\\s*=>\\s*)\\d+/", '${1}8', $out, 1) ?? $out;
        $out = preg_replace("/('live_kfrag_grace_ms'\\s*=>\\s*)\\d+/", '${1}2500', $out, 1) ?? $out;
        $out = preg_replace("/('live_beat_max_fails'\\s*=>\\s*)\\d+/", '${1}1', $out, 1) ?? $out;
    }

    return $out;
}


function og_patch_apply_live_webhook_js_config(string $jsBlockHtml, string $webhookMode, int $webhookTimeoutSec = 2): string
{
    if (strtolower(trim($webhookMode)) !== 'authoritative') {
        return $jsBlockHtml;
    }
    $whMs = max(8000, ((max(1, min(8, $webhookTimeoutSec)) + 2) * 1000) + 1500);
    $out = $jsBlockHtml;
    if (preg_match('/\bliveTimeout\s*:/', $out)) {
        $out2 = preg_replace('/\bliveTimeout\s*:\s*\d+/', 'liveTimeout:' . $whMs, $out, 1);
        if ($out2 !== null) {
            $out = $out2;
        }
    }
    if (preg_match('/\bliveWebhookStrict\s*:/', $out)) {
        $out2 = preg_replace('/\bliveWebhookStrict\s*:\s*\w+/', 'liveWebhookStrict:true', $out, 1);
        if ($out2 !== null) {
            $out = $out2;
        }
    } else {
        $out2 = preg_replace('/(\bliveTimeout\s*:\s*\d+,)/', '$1' . "\n    liveWebhookStrict:true,", $out, 1);
        if ($out2 !== null) {
            $out = $out2;
        }
    }
    if (preg_match('/\bsoftFailOpen\s*:/', $out)) {
        $out2 = preg_replace('/\bsoftFailOpen\s*:\s*\w+/', 'softFailOpen:true', $out, 1);
        if ($out2 !== null) {
            $out = $out2;
        }
    } else {
        $out2 = preg_replace('/(\bliveWebhookStrict\s*:\s*\w+,)/', '$1' . "\n    softFailOpen:true,", $out, 1);
        if ($out2 === null) {
            $out2 = preg_replace('/(\bliveTimeout\s*:\s*\d+,)/', '$1' . "\n    softFailOpen:true,", $out, 1);
        }
        if ($out2 !== null) {
            $out = $out2;
        }
    }

    return $out;
}

function og_patch_apply_bot_webhook_config(string $botProtect, string $webhookMode, string $webhookUrl = ''): string
{
    $mode = strtolower(trim($webhookMode));
    if (!in_array($mode, ['notify', 'authoritative'], true)) {
        $mode = 'notify';
    }
    if ($mode === 'authoritative') {
        $out = preg_replace(
            "/'og_webhook_mode'\\s*=>\\s*'notify'/",
            "'og_webhook_mode'                => 'authoritative'",
            $botProtect,
            1
        );
        if ($out !== null) {
            $botProtect = $out;
        }
    }
    $url = trim($webhookUrl);
    if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
        $exp = var_export($url, true);
        $out = preg_replace(
            "/'og_webhook_url'\\s*=>\\s*''/",
            "'og_webhook_url'                 => " . $exp,
            $botProtect,
            1
        );
        if ($out !== null) {
            $botProtect = $out;
        }
    }

    return $botProtect;
}

function og_patch_apply_live_hook_tab_config(string $botProtect): string
{
    if (preg_match("/'live_hook_one_tab'\\s*=>/", $botProtect)) {
        $out = preg_replace("/'live_hook_one_tab'\\s*=>\\s*\\w+/", "'live_hook_one_tab'              => true", $botProtect, 1);

        return $out ?? $botProtect;
    }
    $out = preg_replace(
        "/('live_pub_reload_grace_window'\\s*=>\\s*\\d+,\\s*\\n)/",
        "$1\n    'live_hook_one_tab'              => true,\n",
        $botProtect,
        1
    );

    return $out ?? $botProtect;
}


function og_patch_apply_live_hook_js_config(string $jsBlockHtml): string
{
    $out = $jsBlockHtml;
    if (!preg_match('/\bliveTabStorageKey\s*:/', $out)) {
        $r = preg_replace(
            '/(\blivePubStorageKey\s*:\s*"[^"]+",)/',
            '$1' . "\n    liveTabStorageKey:\"_ogT1\",\n    liveHookOneTab:true,",
            $out,
            1
        );
        if ($r !== null) {
            $out = $r;
        }
    }
    if (preg_match('/\bliveHookOneTab\s*:/', $out)) {
        $r = preg_replace('/\bliveHookOneTab\s*:\s*\w+/', 'liveHookOneTab:true', $out, 1);
        if ($r !== null) {
            $out = $r;
        }
    }

    return $out;
}

function og_patch_apply_reload_grace_config(string $botProtect, int $perMin = 5): string
{
    $perMin = max(1, $perMin);
    if (preg_match("/'live_pub_reload_grace_per_min'\\s*=>/", $botProtect)) {
        $out = preg_replace(
            "/'live_pub_reload_grace_per_min'\\s*=>\\s*\\d+/",
            "'live_pub_reload_grace_per_min'  => " . $perMin,
            $botProtect,
            1
        );

        return $out ?? $botProtect;
    }
    $out = preg_replace(
        "/('og_ban_bad_token_subnet_per_min'\\s*=>\\s*\\d+,\\s*\\n)/",
        "$1\n    'live_pub_reload_grace_per_min'  => " . $perMin . ",\n    'live_pub_reload_grace_window'   => 60,\n",
        $botProtect,
        1
    );

    return $out ?? $botProtect;
}


function og_patch_harden_bot_protect_php(string $botProtect): string
{
    $keys = ['preflight_on', 'sec_fetch_strict', 'og_webhook_validate', 'live_pub_rotate', 'live_hook_one_tab'];
    foreach ($keys as $key) {
        $pat = '/(?<![\'"])' . preg_quote($key, '/') . '\s*=>/';
        $rep = "'" . $key . "' =>";
        $r = preg_replace($pat, $rep, $botProtect);
        if ($r !== null) {
            $botProtect = $r;
        }
    }

    return $botProtect;
}


function og_patch_apply_bot_auto_protect_profile(string $botProtect, array $profile): string
{
    if (empty($profile['auto_protect_full'])) {
        return $botProtect;
    }
    $out = $botProtect;
    $boolOn = [
        'preflight_on'        => !empty($profile['preflight_on']),
        'sec_fetch_strict'    => !empty($profile['sec_fetch_strict']),
        'og_webhook_validate' => !empty($profile['og_webhook_validate']),
    ];
    foreach ($boolOn as $key => $enable) {
        if (!$enable) {
            continue;
        }
        $pat = "/'" . preg_quote($key, '/') . "'\\s*=>\\s*\\w+/";
        $rep = var_export($key, true) . ' => true';
        $r = preg_replace($pat, $rep, $out, 1);
        if ($r !== null) {
            $out = $r;
        }
    }
    if (!empty($profile['reload_grace'])) {
        $perMin = max(1, (int)($profile['reload_grace_per_min'] ?? 3));
        $out = og_patch_apply_reload_grace_config($out, $perMin);
    }
    if (!empty($profile['live_hook_one_tab'])) {
        $out = og_patch_apply_live_hook_tab_config($out);
    }

    return $out;
}

function og_patch_js_decoy_source(): string
{
    return "(function(){\"use strict\";\n"
        . "var _ogDecoy=function(){var t=Date.now(),h=[],k=[0x4f,0x47,0x5f,0x44,0x45,0x43,0x4f,0x59];"
        . "for(var i=0;i<256;i++)h.push((i*17+t)%251);"
        . "return k.map(function(b,i){return String.fromCharCode(b^h[i%h.length])}).join(\"\")};"
        . "void _ogDecoy();"
        . "throw new Error(\"OG_MODULE_UNAVAILABLE\");"
        . "})();\n";
}


function og_patch_js_bootstrap_source(string $relJson, string $keyB64u, string $canonicalHostLower = ''): string
{
    $kb = json_encode($keyB64u, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $eh = json_encode(strtolower(trim($canonicalHostLower)), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $hmFn = og_patch_js_host_match_fn();

    return '(function(){"use strict";'
        . 'if(window.__ogCopyKilled)return;'
        . 'var R=' . $relJson . ',Kb=' . $kb . ',EH=' . $eh . ',dead=0;'
        . $hmFn
        . 'function ub(s){s=String(s||"").replace(/-/g,"+").replace(/_/g,"/");while(s.length%4)s+="=";'
        . 'try{return Uint8Array.from(atob(s),function(c){return c.charCodeAt(0)&255})}catch(e){return null}}'
        . 'function expHost(){var h=String(EH||"").trim().toLowerCase();if(h)return h;'
        . 'try{var m=document.querySelector(\'meta[name="og-origin-host"]\');if(m)return String(m.getAttribute("content")||"").trim().toLowerCase();}catch(e){}return"";}'
        . 'function copyCtx(){if(String(location.protocol||"")==="file:")return 1;var e=expHost();'
        . 'if(!e)return 0;var lh=String(location.host||"").toLowerCase(),lhn=String(location.hostname||"").toLowerCase();'
        . 'if((lh&&!_ogHm(lh,e))||(lhn&&!_ogHm(lhn,e)))return 1;'
        . 'try{var oh=String((location.origin&&location.origin!=="null")?new URL(location.origin).hostname:"").toLowerCase();if(oh&&!_ogHm(oh,e))return 1;}catch(x){}return 0;}'
        . 'function clr(){try{sessionStorage.removeItem("_ogLt5");sessionStorage.removeItem("_og5");}catch(e){}'
        . 'try{localStorage.removeItem("_ogLt5");localStorage.removeItem("_og5");}catch(e){}}'
        . 'function kill(){if(dead)return;dead=1;clr();'
        . 'try{var o=document.getElementById("og-content");if(o){o.innerHTML="";o.style.display="none";o.style.visibility="hidden";}}catch(e){}'
        . 'if(copyCtx()){try{document.documentElement.innerHTML="";document.documentElement.style.background="#fff";}catch(e2){}}}'
        . 'if(copyCtx()){kill();return;}'
        . 'function go(){'
        . 'if(copyCtx()){kill();return;}'
        . 'if(!globalThis.crypto||!crypto.subtle){if(!copyCtx())window.__ogBootstrapFailed=1;return;}'
        . 'var rk=ub(Kb);if(!rk||rk.length!==32){if(copyCtx())kill();else window.__ogBootstrapFailed=1;return;}'
        . 'var u;try{u=new URL(R,location.href).href;}catch(e){if(copyCtx())kill();return;}'
        . 'var hn=String(location.hostname||"").toLowerCase(),eh=expHost();'
        . 'if(eh&&hn&&!_ogHm(hn,eh)){if(copyCtx())kill();return;}'
        . 'fetch(u,{credentials:"same-origin",cache:"no-store"})'
        . '.then(function(r){if(!r.ok)throw 0;return r.text();})'
        . '.then(function(t){var j=JSON.parse(t);var iv=ub(j.iv),ct=ub(j.ct),tg=ub(j.tag);'
        . 'if(!iv||!ct||!tg||iv.length!==12||tg.length!==16)throw 0;'
        . 'if(copyCtx())throw 0;'
        . 'var aadHost=eh||hn;'
        . 'var aad=new TextEncoder().encode(aadHost);'
        . 'if(eh&&hn&&!_ogHm(hn,eh))throw 0;'
        . 'var buf=new Uint8Array(ct.length+tg.length);buf.set(ct,0);buf.set(tg,ct.length);'
        . 'return crypto.subtle.importKey("raw",rk,{name:"AES-GCM"},false,["decrypt"])'
        . '.then(function(key){return crypto.subtle.decrypt({name:"AES-GCM",iv:iv,additionalData:aad,tagLength:128},key,buf);});})'
        . '.then(function(pt){'
        . 'if(copyCtx())throw 0;'
        . 'var outer=new TextDecoder("utf-8").decode(new Uint8Array(pt));'
        . 'var w=JSON.parse(outer);if(!w||typeof w.p!=="string")throw 0;'
        . 'var innerBytes=ub(w.p);if(!innerBytes)throw 0;'
        . 'var code=new TextDecoder("utf-8").decode(innerBytes);'
        . 'var b=new Blob([code],{type:"text/javascript;charset=utf-8"});'
        . 'var ou=URL.createObjectURL(b);var s=document.createElement("script");s.src=ou;'
        . 's.onload=function(){try{URL.revokeObjectURL(ou);}catch(e){}window.__ogGuardLoaded=1;};'
        . 's.onerror=function(){if(copyCtx())kill();else window.__ogBootstrapFailed=1;};'
        . '(document.head||document.documentElement).appendChild(s);'
        . '})["catch"](function(){if(copyCtx())kill();else window.__ogBootstrapFailed=1;});}'
        . 'go();})();';
}


function og_patch_js_bootstrap_source_live(string $relJson, string $canonicalHostLower = '', string $ogJskEp = 'jsk'): string
{
    $eh = json_encode(strtolower(trim($canonicalHostLower)), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $hmFn = og_patch_js_host_match_fn();
    $epQ = preg_match('/^[A-Za-z0-9_]{1,40}$/', $ogJskEp) ? $ogJskEp : 'jsk';
    $epJson = json_encode('?_og_ep=' . $epQ, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    return '(function(){"use strict";'
        . 'if(window.__ogCopyKilled)return;'
        . 'var R=' . $relJson . ',EH=' . $eh . ',dead=0;'
        . $hmFn
        . 'function ub(s){s=String(s||"").replace(/-/g,"+").replace(/_/g,"/");while(s.length%4)s+="=";'
        . 'try{return Uint8Array.from(atob(s),function(c){return c.charCodeAt(0)&255})}catch(e){return null}}'
        . 'function expHost(){var h=String(EH||"").trim().toLowerCase();if(h)return h;'
        . 'try{var m=document.querySelector(\'meta[name="og-origin-host"]\');if(m)return String(m.getAttribute("content")||"").trim().toLowerCase();}catch(e){}return"";}'
        . 'function copyCtx(){if(String(location.protocol||"")==="file:")return 1;var e=expHost();'
        . 'if(!e)return 0;var lh=String(location.host||"").toLowerCase(),lhn=String(location.hostname||"").toLowerCase();'
        . 'if((lh&&!_ogHm(lh,e))||(lhn&&!_ogHm(lhn,e)))return 1;'
        . 'try{var oh=String((location.origin&&location.origin!=="null")?new URL(location.origin).hostname:"").toLowerCase();if(oh&&!_ogHm(oh,e))return 1;}catch(x){}return 0;}'
        . 'function clr(){try{sessionStorage.removeItem("_ogLt5");sessionStorage.removeItem("_og5");}catch(e){}'
        . 'try{localStorage.removeItem("_ogLt5");localStorage.removeItem("_og5");}catch(e){}}'
        . 'function kill(){if(dead)return;dead=1;clr();'
        . 'try{var o=document.getElementById("og-content");if(o){o.innerHTML="";o.style.display="none";o.style.visibility="hidden";}}catch(e){}'
        . 'try{document.documentElement.innerHTML="";document.documentElement.style.background="#fff";}catch(e2){}}'
        . 'if(copyCtx()){kill();return;}'
        . 'var QQ=' . $epJson . ';var EPS=["/bot-protect.php"+QQ,"bot-protect.php"+QQ,"../bot-protect.php"+QQ,"../../bot-protect.php"+QQ,"../../../bot-protect.php"+QQ];'
        . 'function loadGuard(jk){'
        . 'if(dead||copyCtx())return;'
        . 'if(!globalThis.crypto||!crypto.subtle){return;}'
        . 'var rk=ub(jk);if(!rk||rk.length!==32){if(copyCtx())kill();return;}'
        . 'var bu;try{bu=new URL(R,location.href).href;}catch(e){if(copyCtx())kill();return;}'
        . 'var hn=String(location.hostname||"").toLowerCase(),eh=expHost();'
        . 'if(eh&&hn&&!_ogHm(hn,eh)){if(copyCtx())kill();return;}'
        . 'fetch(bu,{credentials:"same-origin",cache:"no-store"})'
        . '.then(function(r){if(!r.ok)throw 0;return r.text();})'
        . '.then(function(t){var j=JSON.parse(t);var iv=ub(j.iv),ct=ub(j.ct),tg=ub(j.tag);'
        . 'if(!iv||!ct||!tg||iv.length!==12||tg.length!==16)throw 0;'
        . 'if(copyCtx())throw 0;'
        . 'var aad=new TextEncoder().encode(eh||hn);'
        . 'var buf=new Uint8Array(ct.length+tg.length);buf.set(ct,0);buf.set(tg,ct.length);'
        . 'return crypto.subtle.importKey("raw",rk,{name:"AES-GCM"},false,["decrypt"])'
        . '.then(function(key){return crypto.subtle.decrypt({name:"AES-GCM",iv:iv,additionalData:aad,tagLength:128},key,buf);});})'
        . '.then(function(pt){'
        . 'if(copyCtx())throw 0;'
        . 'var outer=new TextDecoder("utf-8").decode(new Uint8Array(pt));'
        . 'var w=JSON.parse(outer);if(!w||typeof w.p!=="string")throw 0;'
        . 'var innerBytes=ub(w.p);if(!innerBytes)throw 0;'
        . 'var code=new TextDecoder("utf-8").decode(innerBytes);'
        . 'var b=new Blob([code],{type:"text/javascript;charset=utf-8"});'
        . 'var ou=URL.createObjectURL(b);var s=document.createElement("script");s.src=ou;'
        . 's.onload=function(){try{URL.revokeObjectURL(ou);}catch(e){}window.__ogGuardLoaded=1;};'
        . 's.onerror=function(){if(copyCtx())kill();};'
        . '(document.head||document.documentElement).appendChild(s);'
        . '})["catch"](function(){if(copyCtx())kill();});}'
        // КРИТИЧНО: tryKey раніше робив 20 запитів за 14 сек (5 EPS × 4 retry × 700ms),
        // кожний бив у suspect-score → bot-protect банив legit user'a за свій ж спам.
        // Тепер: читаємо body навіть на 403; якщо server повертає {r:no_secret/not_origin}
        // — це STOP сигнал, не пробуємо інші EPS, не retry, лишаємо контент як є (відкрито).
        . 'var hardStopReasons={"no_secret":1,"not_origin":1,"no_key":1};'
        . 'function tryKey(i,att){'
        . 'if(dead)return;if(copyCtx()){kill();return;}'
        . 'if(i>=EPS.length){att++;if(att>=2){return;}setTimeout(function(){tryKey(0,att);},2000+Math.random()*1500);return;}'
        . 'var u;try{u=new URL(EPS[i],location.href).href;}catch(e){tryKey(i+1,att);return;}'
        . 'fetch(u,{credentials:"same-origin",cache:"no-store"})'
        . '.then(function(r){return r.text().then(function(t){return{ok:r.ok,status:r.status,body:t};});})'
        . '.then(function(res){'
        // Парсимо JSON якщо є
        . 'var j=null;try{j=JSON.parse(res.body);}catch(e){}'
        // STOP сигнал: server чітко сказав чому не дав ключ → не перебираємо EPS і не retry
        . 'if(j&&j.ok===false&&j.r&&hardStopReasons[j.r]){dead=1;return;}'
        // Успіх
        . 'if(res.ok&&j&&j.ok===true&&typeof j.jk==="string"){if(copyCtx())return;loadGuard(j.jk);return;}'
        // Інакше — пробуємо наступний EP
        . 'tryKey(i+1,att);'
        . '})'
        . '["catch"](function(){tryKey(i+1,att);});}'
        . 'tryKey(0,0);})();';
}


function og_patch_js_pack(string $src): string
{
    if ($src === '' || preg_match('/[^\x00-\x7F]/', $src)) {
        return $src;
    }
    $klen = 16;
    $key = random_bytes($klen);
    $n = strlen($src);
    $xor = '';
    for ($i = 0; $i < $n; $i++) {
        $xor .= chr(ord($src[$i]) ^ ord($key[$i % $klen]));
    }
    $encJs = json_encode(og_patch_b64u($xor), JSON_UNESCAPED_SLASHES);

    $slots = $klen * 3;
    $idx = [];
    $used = [];
    for ($k = 0; $k < $klen; $k++) {
        do { $p = random_int(0, $slots - 1); } while (isset($used[$p]));
        $used[$p] = true;
        $idx[] = $p;
    }
    $arr = [];
    for ($q = 0; $q < $slots; $q++) {
        $arr[$q] = random_int(0, 255);
    }
    for ($k = 0; $k < $klen; $k++) {
        $arr[$idx[$k]] = ord($key[$k]);
    }

    $ids = [];
    for ($v = 0; $v < 9; $v++) {
        $ids[$v] = '_' . substr(bin2hex(random_bytes(4)), 0, 6) . $v;
    }
    [$vA, $vI, $vK, $vE, $vD, $vS, $vO, $vR, $vN] = $ids;

    return '(function(){'
        . 'var ' . $vA . '=[' . implode(',', $arr) . '];'
        . 'var ' . $vI . '=[' . implode(',', $idx) . '];'
        . 'var ' . $vK . '=' . $vI . '.map(function(p){return ' . $vA . '[p];});'
        . 'var ' . $vE . '=' . $encJs . ';'
        . 'function ' . $vD . '(' . $vS . '){'
        . $vS . '=' . $vS . '.replace(/-/g,"+").replace(/_/g,"/");while(' . $vS . '.length%4)' . $vS . '+="=";'
        . 'var ' . $vR . '=atob(' . $vS . '),' . $vO . '="",' . $vN . '=' . $vR . '.length,zz=0;'
        . 'for(;zz<' . $vN . ';zz++){' . $vO . '+=String.fromCharCode(' . $vR . '.charCodeAt(zz)^' . $vK . '[zz%' . $vK . '.length]);}'
        . 'return ' . $vO . ';}'
        . '(0,eval)(' . $vD . '(' . $vE . '));'
        . '})();';
}


function og_patch_build_js_guard_deploy(
    string $jsBlockHtml,
    string $offerPath,
    string $cookieSecret,
    string $canonicalHost,
    bool $dryRun,
    string $ogAssetsSubdir = '_og_assets',
    string $ogAssetsAs = 'js',
    ?string $ogAssetExt = null,
    bool $ogAssetsHtaccess = false,
    bool $ogAssetsNocache = false,
    string $ogWebhookMode = 'notify',
    int $ogWebhookTimeout = 2,
    bool $useExternalBundle = false,
    bool $ogKeySplit = false,
    bool $ogAggressiveMax = false,
    string $payloadKey = '',
    string $ogJskEp = 'jsk'
): string {
    $lc = strtolower(trim($canonicalHost));
    $jsBlockHtml = og_patch_apply_copy_guard_config($jsBlockHtml, $lc);
    $jsBlockHtml = og_patch_apply_live_pub_js_config($jsBlockHtml, 60);
    $jsBlockHtml = og_patch_apply_live_hook_js_config($jsBlockHtml);
    $jsBlockHtml = og_patch_apply_live_webhook_js_config($jsBlockHtml, $ogWebhookMode, $ogWebhookTimeout);
    $jsBlockHtml = og_patch_apply_key_split_js_config($jsBlockHtml, $ogKeySplit, $ogAggressiveMax);
    if (!$useExternalBundle) {
        return og_patch_harden_script_blocks_in_html($jsBlockHtml);
    }
    if ($dryRun) {
        warn('[DRY-RUN] зовнішній зашифрований JS bundle не записується — залишаємо повний inline guard.');

        return og_patch_harden_script_blocks_in_html($jsBlockHtml);
    }
    if (!function_exists('openssl_encrypt')) {
        
        warn('OpenSSL недоступен — зовнішнє шифрування JS guard пропущено (inline як раніше).');

        return og_patch_harden_script_blocks_in_html($jsBlockHtml);
    }
    if ($lc === '') {
        warn('Канонічний host не заданий (--canonical-host= або meta og-origin-host з content) — зовнішнє шифрування JS guard пропущено.');

        return og_patch_harden_script_blocks_in_html($jsBlockHtml);
    }
    if (!preg_match('/<script>\s*([\s\S]*?)<\/script>/i', $jsBlockHtml, $m)) {
        return og_patch_harden_script_blocks_in_html($jsBlockHtml);
    }
    $core = (string)$m[1];
    $saltHex = bin2hex(random_bytes(16));
    $jsLiveMode = $ogKeySplit && $payloadKey !== '';
    if ($jsLiveMode) {
        
        
        $key = hash_hmac('sha256', 'OfferGuardJsBundleV1|' . $lc, og_patch_payload_key_bytes($payloadKey), true);
    } else {
        $key = hash('sha256', $cookieSecret . "\x1c" . $saltHex . "\x1c" . $lc . "\x1cOfferGuardJsGcmV1", true);
    }
    $iv = random_bytes(12);
    $tag = '';
    $wrapped = json_encode([
        'ogv' => 2,
        'sid' => $saltHex,
        'h'   => hash('sha256', $core),
        'p'   => base64_encode($core),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($wrapped === false) {
        return $jsBlockHtml;
    }
    $ct = openssl_encrypt($wrapped, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, $lc);
    if (!is_string($ct) || strlen($tag) !== 16) {
        warn('openssl_encrypt JS guard не вдався — inline fallback.');

        return $jsBlockHtml;
    }
    $rid = bin2hex(random_bytes(4));
    $subdir = trim($ogAssetsSubdir, '/');
    if ($subdir === '') {
        $subdir = '_og_assets';
    }
    $classicJs = ($ogAssetsAs === 'js' && $ogAssetExt === null);
    if ($ogAssetExt !== null && $ogAssetExt !== '') {
        $realExt = $ogAssetExt;
    } elseif ($ogAssetsAs === 'png') {
        $realExt = '.png';
    } elseif ($ogAssetsAs === 'webp') {
        $realExt = '.webp';
    } else {
        $realExt = '.js';
    }
    if ($classicJs) {
        
        
        $rel = $subdir . '/chunk.' . $rid . '.js';
        $decoys = [
            $subdir . '/app.' . substr(bin2hex(random_bytes(4)), 0, 8) . '.js',
            $subdir . '/vendors.' . substr(bin2hex(random_bytes(4)), 0, 8) . '.js',
            $subdir . '/polyfills.' . substr(bin2hex(random_bytes(4)), 0, 8) . '.js',
        ];
    } else {
        $rel = $subdir . '/' . bin2hex(random_bytes(16)) . $realExt;
        $decoys = [
            $subdir . '/' . bin2hex(random_bytes(14)) . '.gif',
            $subdir . '/' . bin2hex(random_bytes(14)) . '.jpg',
            $subdir . '/' . bin2hex(random_bytes(14)) . '.jpeg',
        ];
    }
    $bundle = json_encode([
        'v'   => 1,
        'sid' => $saltHex,
        'iv'  => og_patch_b64u($iv),
        'tag' => og_patch_b64u($tag),
        'ct'  => og_patch_b64u($ct),
    ], JSON_UNESCAPED_SLASHES);
    if ($bundle === false) {
        return $jsBlockHtml;
    }
    $assetDir = $offerPath . '/' . $subdir;
    if (!is_dir($assetDir) && !@mkdir($assetDir, 0755, true)) {
        warn('Не вдалося створити ' . $subdir . ' — inline fallback.');

        return $jsBlockHtml;
    }
    og_patch_clean_og_assets_dir($assetDir);
    $wReal = @file_put_contents($assetDir . '/' . basename($rel), $bundle, LOCK_EX);
    if ($wReal === false) {
        warn('Не вдалося записати JS bundle — inline fallback.');

        return $jsBlockHtml;
    }
    foreach ($decoys as $drel) {
        @file_put_contents($assetDir . '/' . basename($drel), og_patch_js_decoy_source(), LOCK_EX);
    }
    og_patch_write_og_assets_apache_config($assetDir, $lc, $ogAssetsHtaccess, $ogAssetsNocache);
    info('JS guard: зашифрований bundle ' . $rel . ' + decoy ' . count($decoys) . ' файли в ' . $subdir . '/');
    $relJson = json_encode($rel, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($jsLiveMode) {
        
        
        $bootstrap = og_patch_js_bootstrap_source_live($relJson, $lc, $ogJskEp);
        $bootstrap = og_patch_js_pack($bootstrap);
        info('JS guard: live-gated bundle key (origin-only, per-deploy ep), packed bootstrap, plaintext fallback вимкнено.');

        return "<!-- [OfferGuard:start] -->\n"
            . og_patch_wrap_inline_script("\n" . $bootstrap . "\n")
            . "\n<!-- [OfferGuard:end] -->";
    }
    $keyB64u = og_patch_b64u($key);
    $bootstrap = og_patch_js_bootstrap_source($relJson, $keyB64u, $lc);
    $inlineFallback = og_patch_wrap_inline_js_fallback($jsBlockHtml);

    return "<!-- [OfferGuard:start] -->\n" . og_patch_wrap_inline_script("\n" . $bootstrap . "\n") . "\n"
        . "<!-- [OfferGuard:inline-fallback] -->\n" . $inlineFallback . "\n<!-- [OfferGuard:end] -->";
}

function og_defer_attrs(string $tag, array $attrs, string $payloadKey, string $lc = '', string $nonce = '', bool $splitKey = false): string
{
    $useV2 = ($lc !== '' && $nonce !== '');
    foreach ($attrs as $attr) {
        if (preg_match('/\sdata-og-enc-' . preg_quote($attr, '/') . '\s*=/i', $tag)) continue;
        $tag = preg_replace_callback(
            '/\sdata-og-' . preg_quote($attr, '/') . '\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i',
            static function ($m) use ($attr, $payloadKey, $lc, $nonce, $useV2, $splitKey) {
                $val = ($m[2] ?? '') !== '' ? $m[2] : ((($m[3] ?? '') !== '') ? $m[3] : ($m[4] ?? ''));
                $enc = $useV2 ? og_patch_encrypt_payload_v2($val, $payloadKey, $lc, $nonce, $splitKey) : og_patch_encrypt_payload($val, $payloadKey);
                return ' data-og-enc-' . strtolower($attr) . '="' . htmlspecialchars($enc, ENT_QUOTES, 'UTF-8') . '"';
            },
            $tag
        );
        $tag = preg_replace_callback(
            '/\s(' . preg_quote($attr, '/') . ')\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i',
            static function ($m) use ($attr, $payloadKey, $lc, $nonce, $useV2, $splitKey) {
                $val = ($m[3] ?? '') !== '' ? $m[3] : ((($m[4] ?? '') !== '') ? $m[4] : ($m[5] ?? ''));
                $enc = $useV2 ? og_patch_encrypt_payload_v2($val, $payloadKey, $lc, $nonce, $splitKey) : og_patch_encrypt_payload($val, $payloadKey);
                return ' data-og-enc-' . strtolower($attr) . '="' . htmlspecialchars($enc, ENT_QUOTES, 'UTF-8') . '"';
            },
            $tag
        );
    }
    return $tag;
}


function og_patch_map_style_tags(string $html, callable $callback): string
{
    $out = '';
    $len = strlen($html);
    $o = 0;
    while ($o < $len) {
        $p = stripos($html, '<style', $o);
        if ($p === false) {
            return $out . substr($html, $o);
        }
        $out .= substr($html, $o, $p - $o);
        $gt = strpos($html, '>', $p);
        if ($gt === false) {
            return $out . substr($html, $p);
        }
        $attrs = substr($html, $p + 6, $gt - ($p + 6));
        $innerStart = $gt + 1;
        $i = $innerStart;
        $state = 0;
        $closeIdx = false;
        $closeTag = '';
        while ($i < $len) {
            $ch = $html[$i];
            if ($state === 3) {
                if ($ch === '*' && $i + 1 < $len && $html[$i + 1] === '/') {
                    $state = 0;
                    $i += 2;
                    continue;
                }
                $i++;
                continue;
            }
            if ($state === 1) {
                if ($ch === '\\' && $i + 1 < $len) {
                    $i += 2;
                    continue;
                }
                if ($ch === '"') {
                    $state = 0;
                }
                $i++;
                continue;
            }
            if ($state === 2) {
                if ($ch === '\\' && $i + 1 < $len) {
                    $i += 2;
                    continue;
                }
                if ($ch === "'") {
                    $state = 0;
                }
                $i++;
                continue;
            }
            if ($ch === '/' && $i + 1 < $len && $html[$i + 1] === '*') {
                $state = 3;
                $i += 2;
                continue;
            }
            if ($ch === '"') {
                $state = 1;
                $i++;
                continue;
            }
            if ($ch === "'") {
                $state = 2;
                $i++;
                continue;
            }
            if ($ch === '<' && preg_match('/^<\/style\s*>/i', substr($html, $i), $xm)) {
                $closeIdx = $i;
                $closeTag = $xm[0];
                break;
            }
            $i++;
        }
        if ($closeIdx === false) {
            return $out . substr($html, $p);
        }
        $inner = substr($html, $innerStart, $closeIdx - $innerStart);
        $out .= $callback($attrs, $inner);
        $o = $closeIdx + strlen($closeTag);
    }
    return $out;
}

function og_defer_landing_assets(string $html, string $payloadKey, string $lc = '', string $nonce = '', bool $splitKey = false): string
{
    $useV2 = ($lc !== '' && $nonce !== '');
    $encInline = static function (string $plain) use ($payloadKey, $lc, $nonce, $useV2, $splitKey): string {
        return $useV2 ? og_patch_encrypt_payload_v2($plain, $payloadKey, $lc, $nonce, $splitKey) : og_patch_encrypt_payload($plain, $payloadKey);
    };

    $html = og_patch_map_style_tags($html, static function (string $attrs, string $inner) use ($payloadKey, $lc, $nonce, $encInline): string {
        $open = '<style' . $attrs . '>';
        $verbatim = $open . $inner . '</style>';
        if (stripos($open, 'data-og-style') !== false) {
            return $verbatim;
        }
        if (!preg_match('/\sdata-og-type\s*=/i', $open) && preg_match('/\stype\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $open, $tm)) {
            $type = $tm[2] !== '' ? $tm[2] : ($tm[3] !== '' ? $tm[3] : $tm[4]);
            $open = preg_replace('/\stype\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', ' data-og-type="' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '" type="text/plain"', $open, 1);
        } elseif (!preg_match('/\stype\s*=/i', $open)) {
            $open = preg_replace('/>$/', ' type="text/plain">', $open);
        }
        $open = preg_replace('/>$/', ' data-og-style="1" data-og-deferred="1" data-og-enc-inline="1">', $open);
        return $open . $encInline($inner) . '</style>';
    });

    $html = preg_replace_callback('/<script\b([^>]*)>(.*?)<\/script>/is', static function ($m) use ($payloadKey, $lc, $nonce, $splitKey, $encInline) {
        $tag = $m[0];
        if (stripos($tag, '[OfferGuard:start]') !== false) return $tag;
        $open = '<script' . $m[1] . '>';
        $open = og_defer_attrs($open, ['src'], $payloadKey, $lc, $nonce, $splitKey);
        if (!preg_match('/\sdata-og-type\s*=/i', $open) && preg_match('/\stype\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', $open, $tm)) {
            $type = $tm[2] !== '' ? $tm[2] : ($tm[3] !== '' ? $tm[3] : $tm[4]);
            $open = preg_replace('/\stype\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', ' data-og-type="' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '" type="text/plain"', $open, 1);
        } elseif (!preg_match('/\stype\s*=/i', $open)) {
            $open = preg_replace('/>$/', ' type="text/plain">', $open);
        }
        if (!preg_match('/\sdata-og-script\s*=/i', $open)) $open = preg_replace('/>$/', ' data-og-script="1">', $open);
        if (!preg_match('/\sdata-og-deferred\s*=/i', $open)) $open = preg_replace('/>$/', ' data-og-deferred="1">', $open);
        $body = $m[2];
        if ($body !== '' && !preg_match('/\sdata-og-enc-inline\s*=/i', $open)) {
            $body = $encInline($body);
            $open = preg_replace('/>$/', ' data-og-enc-inline="1">', $open);
        }
        return $open . $body . '</script>';
    }, $html);

    $html = preg_replace_callback('/<(img|iframe|source|video|audio|embed|track)\b[^>]*>/i', static function ($m) use ($payloadKey, $lc, $nonce, $splitKey) {
        $tag = og_defer_attrs($m[0], ['src', 'srcset', 'poster'], $payloadKey, $lc, $nonce, $splitKey);
        return preg_replace('/\s*\/?>$/', ' data-og-deferred="1">', $tag);
    }, $html);

    $html = preg_replace_callback('/<object\b[^>]*>/i', static function ($m) use ($payloadKey, $lc, $nonce, $splitKey) {
        $tag = og_defer_attrs($m[0], ['data'], $payloadKey, $lc, $nonce, $splitKey);
        return preg_replace('/\s*\/?>$/', ' data-og-deferred="1">', $tag);
    }, $html);

    $html = preg_replace_callback('/<link\b[^>]*\bhref\s*=\s*[^>]+>/i', static function ($m) use ($payloadKey, $lc, $nonce, $splitKey) {
        $tag = $m[0];
        if (!preg_match('/\brel\s*=\s*("|\')?(stylesheet|preload|modulepreload|icon|apple-touch-icon|manifest)\b/i', $tag)) return $tag;
        $tag = og_defer_attrs($tag, ['href'], $payloadKey, $lc, $nonce, $splitKey);
        return preg_replace('/\s*\/?>$/', ' data-og-deferred="1">', $tag);
    }, $html);

    return $html;
}


function og_patch_extract_og_content_inner(string $html): ?array
{
    if (!preg_match('/<div\b[^>]*\bid\s*=\s*(["\'])og-content\1[^>]*>/i', $html, $m, PREG_OFFSET_CAPTURE)) {
        return null;
    }
    $openTag = (string)$m[0][0];
    $start = (int)$m[0][1] + strlen($openTag);
    $depth = 1;
    $i = $start;
    $len = strlen($html);
    while ($i < $len && $depth > 0) {
        $nextOpen = stripos($html, '<div', $i);
        $nextClose = stripos($html, '</div', $i);
        if ($nextClose === false) {
            return null;
        }
        if ($nextOpen !== false && $nextOpen < $nextClose) {
            $depth++;
            $i = $nextOpen + 4;
            continue;
        }
        $depth--;
        if ($depth === 0) {
            return [
                'open'  => $openTag,
                'inner' => substr($html, $start, $nextClose - $start),
                'start' => (int)$m[0][1],
                'end'   => $nextClose + 6,
            ];
        }
        $i = $nextClose + 5;
    }

    return null;
}


function og_patch_scrape_harden_og_content(string $html, string $payloadKey, string $canonicalHostLower = '', bool $splitKey = false, bool $strictFail = false): array
{
    if ($payloadKey === '' || !function_exists('openssl_encrypt')) {
        if ($strictFail) {
            fail('scrape-harden: OpenSSL або payload key відсутні — #og-content лишився plaintext');
            exit(1);
        }

        return [$html, false];
    }
    $lc = strtolower(trim($canonicalHostLower));
    $block = og_patch_extract_og_content_inner($html);
    if ($block === null) {
        return [$html, true];
    }
    if (preg_match('/\bdata-og-enc-html\s*=/i', $block['open'])) {
        return [$html, true];
    }
    $inner = trim((string)$block['inner']);
    if ($inner === '') {
        return [$html, true];
    }
    $inner = preg_replace('/<!--[\s\S]*?-->/', '', $inner) ?? $inner;
    $inner = trim($inner);
    if ($inner === '') {
        return [$html, true];
    }
    $nonce = '';
    if (preg_match('/<meta\b[^>]*\bname\s*=\s*(["\'])og-challenge\1[^>]*\bcontent\s*=\s*\1([^\1>]+)\1/i', $html, $cm)) {
        $nonce = trim(html_entity_decode((string)$cm[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
    if ($nonce === '') {
        $nonce = og_patch_challenge_nonce();
        $html = og_patch_inject_challenge_meta($html, $nonce);
    }
    try {
        $encBody = $lc !== ''
            ? og_patch_encrypt_payload_v2($inner, $payloadKey, $lc, $nonce, $splitKey)
            : og_patch_encrypt_payload($inner, $payloadKey);
    } catch (Throwable $e) {
        if ($strictFail) {
            fail('scrape-harden: encrypt og-content failed — ' . $e->getMessage());
            exit(1);
        }
        warn('scrape-harden: encrypt og-content failed — ' . $e->getMessage());
        $encBody = '';
    }
    if ($encBody === '') {
        if ($strictFail) {
            fail('scrape-harden: encrypt og-content повернув порожній blob — plaintext лишився у #og-content');
            exit(1);
        }
        $openTag = (string)$block['open'];
        if (!preg_match('/\bstyle\s*=/i', $openTag)) {
            $openTag = preg_replace('/\s*>$/', ' style="display:none;visibility:hidden">', $openTag, 1) ?? $openTag;
        }
        $replacement = $openTag . '</div>';

        return [substr($html, 0, $block['start']) . $replacement . substr($html, $block['end']), false];
    }
    $openTag = (string)$block['open'];
    if (!preg_match('/\bstyle\s*=/i', $openTag)) {
        $openTag = preg_replace('/\s*>$/', ' style="display:none;visibility:hidden">', $openTag, 1) ?? $openTag;
    } elseif (!preg_match('/display\s*:\s*none/i', $openTag)) {
        $openTag = preg_replace(
            '/\bstyle\s*=\s*(["\'])([^"\']*)\1/i',
            ' style="$2;display:none;visibility:hidden"',
            $openTag,
            1
        ) ?? $openTag;
    }
    $verAttr = $lc !== '' ? ' data-og-enc-ver="2"' : '';
    $lockAttr = $lc !== '' ? ' data-og-canonical-lock="' . htmlspecialchars($lc, ENT_QUOTES, 'UTF-8') . '"' : '';
    $encAttr = ' data-og-enc-html="' . htmlspecialchars($encBody, ENT_QUOTES, 'UTF-8') . '"' . $verAttr . $lockAttr;
    if (preg_match('/\bdata-og-enc-html\s*=/i', $openTag)) {
        $newOpen = $openTag;
    } else {
        $newOpen = preg_replace('/\s*>$/', $encAttr . '>', $openTag, 1) ?? $openTag;
    }
    $replacement = $newOpen . '</div>';

    return [substr($html, 0, $block['start']) . $replacement . substr($html, $block['end']), true];
}

function og_patch_is_ancillary_html(string $relPath): bool
{
    $rel = strtolower(str_replace('\\', '/', ltrim($relPath, '/')));

    // Classic error pages.
    if (preg_match('#^(document_errors/|errors/)#', $rel) === 1) return true;
    if (preg_match('#^(403|404|50x)\.html$#', $rel) === 1) return true;

    // HTML files inside asset/resource subdirectories are supporting files, not landing pages.
    // Hard-fail strict mode is skipped for them; protection warnings are still emitted as warn().
    if (preg_match('#^(assets|static|vendor|js|css|images|img|fonts|media|files|uploads|dist|build|public|src|lib|components|partials|templates|includes)/.*\.html?$#', $rel) === 1) return true;

    return false;
}

/**
 * Repair broken void tags in <head> like:
 *   <meta ... "<div ...>
 * by inserting missing '>' before the next tag start.
 */
function og_patch_repair_broken_head_void_tags(string $html): string
{
    if ($html === '' || stripos($html, '<head') === false) {
        return $html;
    }
    $openPos = stripos($html, '<head');
    if ($openPos === false) {
        return $html;
    }
    $openEnd = strpos($html, '>', $openPos);
    if ($openEnd === false) {
        return $html;
    }
    $closePos = stripos($html, '</head', $openEnd + 1);
    if ($closePos === false || $closePos <= $openEnd) {
        return $html;
    }

    $head = substr($html, $openEnd + 1, $closePos - ($openEnd + 1));
    $scan = 0;
    $voidTags = ['meta', 'link', 'base', 'img', 'input', 'source', 'track', 'area', 'param', 'col', 'embed', 'br', 'hr'];

    while ($scan < strlen($head)) {
        $next = null;
        $nextTag = '';
        foreach ($voidTags as $tag) {
            $p = stripos($head, '<' . $tag, $scan);
            if ($p === false) {
                continue;
            }
            if ($next === null || $p < $next) {
                $next = $p;
                $nextTag = $tag;
            }
        }
        if ($next === null) {
            break;
        }

        $tagStart = $next;
        $nextGt = strpos($head, '>', $tagStart);
        $nextLt = strpos($head, '<', $tagStart + 1);
        if ($nextLt !== false && ($nextGt === false || $nextLt < $nextGt)) {
            // Insert missing terminator for broken void tag.
            $head = substr($head, 0, $nextLt) . '>' . substr($head, $nextLt);
            $scan = $nextLt + 1;
            continue;
        }

        if ($nextGt === false) {
            // End-of-head broken tag without closing '>'
            $head .= '>';
            break;
        }
        $scan = $nextGt + 1;
        unset($nextTag);
    }

    return substr($html, 0, $openEnd + 1) . $head . substr($html, $closePos);
}


function og_patch_ensure_html_structure(string $html): string
{
    $html = og_patch_repair_broken_head_void_tags($html);
    if (!preg_match('/<html\b/i', $html)) {
        return "<!DOCTYPE html>\n<html>\n<head>\n<meta charset=\"utf-8\">\n</head>\n<body>\n" . $html . "\n</body>\n</html>\n";
    }
    if (!preg_match('#</head\s*>#i', $html)) {
        $html = preg_replace('/(<html\b[^>]*>)/i', "$1\n<head>\n<meta charset=\"utf-8\">\n</head>", $html, 1) ?? $html;
    }
    if (!preg_match('/<head\b/i', $html)) {
        $html = preg_replace('/(<html\b[^>]*>)/i', "$1\n<head>\n<meta charset=\"utf-8\">\n</head>", $html, 1) ?? $html;
    }
    if (!preg_match('/<body\b/i', $html)) {
        $html = preg_replace('#</head\s*>#i', "</head>\n<body>", $html, 1) ?? $html;
        if (!preg_match('#</body\s*>#i', $html)) {
            if (preg_match('#</html\s*>#i', $html)) {
                $html = preg_replace('#</html\s*>#i', "</body>\n</html>", $html, 1) ?? $html;
            } else {
                $html .= "\n</body>";
            }
        }
    } elseif (!preg_match('#</body\s*>#i', $html)) {
        if (preg_match('#</html\s*>#i', $html)) {
            $html = preg_replace('#</html\s*>#i', "</body>\n</html>", $html, 1) ?? $html;
        } else {
            $html .= "\n</body>";
        }
    }
    if (!preg_match('#</html\s*>#i', $html)) {
        $html .= "\n</html>";
    }

    return $html;
}


function og_strip_offer_guard_fragments(string $html): string
{
    // Універсальний strip: ВСІ `<!-- [OfferGuard:NAME] -->...<!-- [/OfferGuard:NAME] -->`
    // блоки (origin-soft, copy-reject, copy-live, copy-ui, nuclear, body-gate, early,
    // noscript-harden, та будь-які майбутні). Один regex замість 8.
    $html = preg_replace(
        '/<!--\s*\[OfferGuard:([a-z0-9_\-]+)\]\s*-->[\s\S]*?<!--\s*\[\/OfferGuard:\1\]\s*-->\s*/i',
        '',
        $html
    ) ?? $html;
    // start..end (інші маркери)
    $html = preg_replace('/<!--\s*\[OfferGuard:start\]\s*-->.*?<!--\s*\[OfferGuard:end\]\s*-->/is', '', $html) ?? $html;
    // OG-специфічні DOM elements
    $html = preg_replace('/<div\b[^>]*>\s*<!--\s*\[OfferGuard:trap\]\s*-->.*?<\/div>\s*/is', '', $html) ?? $html;
    $html = preg_replace('/<div\b[^>]*\bid\s*=\s*(["\'])_og_loader\1[^>]*>.*?<\/div>\s*/is', '', $html) ?? $html;
    // OG v4 атрибути на <html> і <body>
    $html = preg_replace('/\s+data-og-canonical-lock\s*=\s*"[^"]*"/i', '', $html) ?? $html;
    $html = preg_replace('/\s+data-og-(?:enc|origin|canonical|encrypt|nonce|payload)[a-z\-]*\s*=\s*"[^"]*"/i', '', $html) ?? $html;
    // OG meta tags
    $html = preg_replace('/<meta\s+name\s*=\s*"og-(?:origin-host|expected-host|origin-session|challenge|canonical)"[^>]*>\s*/i', '', $html) ?? $html;

    return og_patch_strip_static_origin_bypass($html);
}


function og_strip_php_offer_guard_blocks(string $src): string
{
    // PHP-level OG block: /* [OfferGuard:start] */ ... /* [OfferGuard:end] */
    $src = preg_replace('/\/\*\s*\[OfferGuard:start\]\s*\*\/.*?\/\*\s*\[OfferGuard:end\]\s*\*\//s', '', $src) ?? $src;
    // PHP файли часто містять HTML output теж — застосуємо HTML-strip
    return og_strip_offer_guard_fragments($src);
}

function og_extract_html_attr(string $tag, string $attr): ?string
{
    $q = preg_quote($attr, '/');
    if (preg_match('/\s' . $q . '\s*=\s*(["\'])(.*?)\1/is', $tag, $m)) {
        return html_entity_decode((string)$m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    if (preg_match('/\s' . $q . '\s*=\s*([^\s>]+)/is', $tag, $m)) {
        return html_entity_decode((string)$m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    return null;
}

function og_extract_tag_attrs(string $html, string $tagName): string
{
    $tagQ = preg_quote($tagName, '/');
    if (!preg_match('/<' . $tagQ . '\b([^>]*)>/i', $html, $hm)) {
        return '';
    }
    return trim((string)($hm[1] ?? ''));
}

function og_guard_shell(
    string $htmlAttrs,
    string $bodyAttrs,
    string $encHead,
    string $encBody,
    string $encTitle = '',
    string $encHtmlAttrs = '',
    string $encBodyAttrs = '',
    string $canonicalHostLower = '',
    string $challengeNonce = ''
): string {
    $titleAttr = $encTitle !== '' ? ' data-og-enc-title="' . htmlspecialchars($encTitle, ENT_QUOTES, 'UTF-8') . '"' : '';
    $htmlAttrBlob = $encHtmlAttrs !== '' ? ' data-og-enc-html-attrs="' . htmlspecialchars($encHtmlAttrs, ENT_QUOTES, 'UTF-8') . '"' : '';
    $bodyAttrBlob = $encBodyAttrs !== '' ? ' data-og-enc-body-attrs="' . htmlspecialchars($encBodyAttrs, ENT_QUOTES, 'UTF-8') . '"' : '';
    $lc = strtolower(trim($canonicalHostLower));
    $nonce = trim($challengeNonce);
    $htmlLock = $lc !== '' ? ' data-og-canonical-lock="' . htmlspecialchars($lc, ENT_QUOTES, 'UTF-8') . '"' : '';
    $verAttr = ($lc !== '' && $nonce !== '') ? ' data-og-enc-ver="2"' : '';
    $lockAttr = $lc !== '' ? ' data-og-canonical-lock="' . htmlspecialchars($lc, ENT_QUOTES, 'UTF-8') . '"' : '';
    $htmlOpenAttrs = ($htmlAttrs !== '' ? ' ' . $htmlAttrs : '') . $htmlLock;
    $bodyOpenAttrs = $bodyAttrs !== '' ? ' ' . $bodyAttrs : '';
    $copyLive = og_patch_copy_live_defense_snippet($canonicalHostLower);
    $nuclear = og_patch_nuclear_copy_gate_snippet($canonicalHostLower);
    $early = og_patch_early_copy_gate_snippet($canonicalHostLower);
    $earlyBlock = ($copyLive !== '' ? $copyLive . "\n" : '')
        . ($nuclear !== '' ? $nuclear . "\n" : '')
        . ($early !== '' ? $early . "\n" : '');
    $originMeta = '';
    if ($lc !== '') {
        $originMeta = '<meta name="og-origin-host" content="' . htmlspecialchars($lc, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }
    $challengeMeta = $nonce !== '' ? '<meta name="og-challenge" content="' . htmlspecialchars($nonce, ENT_QUOTES, 'UTF-8') . '">' . "\n" : '';

    return '<!DOCTYPE html>' . "\n"
        . '<html' . $htmlOpenAttrs . '>' . "\n"
        . '<head>' . "\n"
        . '<meta charset="UTF-8">' . "\n"
        . $originMeta
        . $challengeMeta
        . $earlyBlock
        . '<meta name="viewport" content="width=device-width,initial-scale=1">' . "\n"
        . '<meta name="robots" content="noindex,nofollow,noarchive">' . "\n"
        . '<title> </title>' . "\n"
        . '</head>' . "\n"
        . '<body' . $bodyOpenAttrs . '><div id="og-content" style="display:none;visibility:hidden" data-og-enc-head="' . htmlspecialchars($encHead, ENT_QUOTES, 'UTF-8') . '" data-og-enc-html="' . htmlspecialchars($encBody, ENT_QUOTES, 'UTF-8') . '"' . $verAttr . $lockAttr . $titleAttr . $htmlAttrBlob . $bodyAttrBlob . '></div></body>' . "\n"
        . '</html>';
}

function og_encrypt_landing_body(string $html, string $payloadKey, string $canonicalHostLower = '', bool $splitKey = false): string
{
    $html = og_strip_offer_guard_fragments($html);
    $lc = strtolower(trim($canonicalHostLower));
    $nonce = '';
    if (preg_match('/<meta\b[^>]*\bname\s*=\s*(["\'])og-challenge\1[^>]*\bcontent\s*=\s*\1([^\1>]+)\1/i', $html, $cm)) {
        $nonce = trim(html_entity_decode((string)$cm[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
    if ($lc !== '' && $nonce === '') {
        $nonce = og_patch_challenge_nonce();
        $html = og_patch_inject_challenge_meta($html, $nonce);
    }
    $htmlAttrs = og_extract_tag_attrs($html, 'html');
    $bodyAttrs = og_extract_tag_attrs($html, 'body');
    $encLanding = static function (string $plain) use ($payloadKey, $lc, $nonce, $splitKey): string {
        if ($lc !== '' && $nonce !== '') {
            return og_patch_encrypt_payload_v2($plain, $payloadKey, $lc, $nonce, $splitKey);
        }

        return og_patch_encrypt_payload($plain, $payloadKey);
    };

    if (preg_match('/<div\b[^>]*\bid\s*=\s*(["\'])og-content\1[^>]*>/is', $html, $cm)) {
        $tag = $cm[0];
        $encBody = og_extract_html_attr($tag, 'data-og-enc-html') ?? '';
        if ($encBody !== '') {
            $encHead = og_extract_html_attr($tag, 'data-og-enc-head') ?? '';
            $encTitle = og_extract_html_attr($tag, 'data-og-enc-title') ?? '';
            if ($encHead === '') {
                $head = '';
                if (preg_match('/<head\b[^>]*>(.*?)<\/head>/is', $html, $hm)) {
                    $head = (string)($hm[1] ?? '');
                }
                $head = preg_replace('/<title\b[^>]*>.*?<\/title>/is', '', $head, 1) ?? $head;
                $head = og_defer_landing_assets(og_strip_offer_guard_fragments($head), $payloadKey, $lc, $nonce, $splitKey);
                $encHead = $encLanding($head);
            }
            $encHtmlAttrs = $htmlAttrs !== '' ? $encLanding($htmlAttrs) : '';
            $encBodyAttrs = $bodyAttrs !== '' ? $encLanding($bodyAttrs) : '';
            return og_guard_shell($htmlAttrs, $bodyAttrs, $encHead, $encBody, $encTitle, $encHtmlAttrs, $encBodyAttrs, $canonicalHostLower, $nonce);
        }
    }

    $head = '';
    if (preg_match('/<head\b[^>]*>(.*?)<\/head>/is', $html, $hm)) {
        $head = (string)($hm[1] ?? '');
    }

    $title = '';
    $head = preg_replace_callback('/<title\b[^>]*>(.*?)<\/title>/is', static function ($m) use (&$title) {
        $title = html_entity_decode(trim((string)($m[1] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return '';
    }, $head, 1) ?? $head;
    $head = og_defer_landing_assets(og_strip_offer_guard_fragments($head), $payloadKey, $lc, $nonce, $splitKey);

    $body = '';
    if (preg_match('/<body\b[^>]*>(.*?)<\/body>/is', $html, $bm)) {
        $body = (string)($bm[1] ?? '');
    }
    $body = og_strip_offer_guard_fragments($body);
    if (preg_match('/^\s*<div\b[^>]*\bid\s*=\s*(["\'])og-content\1[^>]*>(.*)<\/div>\s*$/is', $body, $wm)) {
        $body = (string)$wm[2];
    }
    $body = og_defer_landing_assets($body, $payloadKey, $lc, $nonce, $splitKey);
    $lcWm = strtolower(trim($canonicalHostLower));
    if ($lcWm !== '') {
        $body = '<!-- og:canonical=' . htmlspecialchars($lcWm, ENT_QUOTES, 'UTF-8') . " -->\n" . $body;
    }

    $encHead  = $encLanding($head);
    $encBody  = $encLanding($body);
    $encTitle = $title !== '' ? $encLanding($title) : '';
    $encHtmlAttrs = $htmlAttrs !== '' ? $encLanding($htmlAttrs) : '';
    $encBodyAttrs = $bodyAttrs !== '' ? $encLanding($bodyAttrs) : '';

    return og_guard_shell($htmlAttrs, $bodyAttrs, $encHead, $encBody, $encTitle, $encHtmlAttrs, $encBodyAttrs, $canonicalHostLower, $nonce);
}

if (!$autoProtect) {
    info('мінімальний патч (--no-auto-protect): guard + bot-protect, без encrypt body / early gate');
}




head("ФРОНТЕНД — HTML");
$hPatch = $hSkip = 0;
foreach ($htmlFiles as $p) {
    $rel = str_replace($offerPath . '/', '', $p);
    $src = file_get_contents($p);
    if ((string)$src !== '' && og_patch_has_broken_docopen_fragment_outside_script((string)$src)) {
        $bk = $backupDir . '/' . md5($p);
        if (is_file($bk)) {
            $orig = (string)@file_get_contents($bk);
            if ($orig !== '' && !og_patch_has_broken_docopen_fragment_outside_script($orig)) {
                $src = $orig;
                info('[OfferGuard] ' . $rel . ': файл містив битий JS з попереднього патчу → відновлюю з _og_backup, патчу заново');
            } else {
                warn('[OfferGuard] ' . $rel . ': файл і бекап обидва містять битий JS — спершу --cleanup, потім вручну поверни оригінал');
            }
        } else {
            warn('[OfferGuard] ' . $rel . ': битий JS, бекап відсутній — патч цього файлу буде перервано');
        }
    }
    if (!og_patch_is_html_like_file($p, $src)) {
        $hSkip++;

        continue;
    }

    $hadOg = str_contains($src, '[OfferGuard:start]');

    $out = og_strip_offer_guard_fragments($src);

    $out = og_patch_ensure_html_structure($out);


    if (stripos($out, '<form') !== false && !str_contains($out, "name='_w'")) {
        $out = preg_replace_callback('/<form([^>]*)>/i',
            fn($m) => $m[0] . "\n" . $HP_HTML, $out);
    }
    if (stripos($out, '<form') !== false && !str_contains($out, 'og_live_token')) {
        $ogLiveHidden = '<input type="hidden" class="_og_live_tok" name="og_live_token" value="" autocomplete="off">';
        $out = preg_replace_callback('/<form([^>]*)>/i',
            static fn($m) => $m[0] . "\n" . $ogLiveHidden, $out);
    }

    
    if (!str_contains(strtolower($out), 'name="robots"')) {
        $out = og_patch_safe_inject_before_head_close($out,
            '  <meta name="robots" content="noindex,nofollow,noarchive">');
    }
    if (!str_contains(strtolower($out), 'name="og-origin-host"')) {
        $out = og_patch_safe_inject_before_head_close($out,
            '  <meta name="og-origin-host" content="">');
    }
    if ($canonicalHost !== '' && $autoProtectProfile !== []) {
        if (!preg_match('/\bname\s*=\s*(["\'])og-challenge\1/i', $out)) {
            $out = og_patch_inject_challenge_meta($out, og_patch_challenge_nonce());
        }
        if (preg_match('/<html\b/i', $out) && stripos($out, 'data-og-canonical-lock') === false) {
            $lockAttr = htmlspecialchars(strtolower($canonicalHost), ENT_QUOTES, 'UTF-8');
            $out = preg_replace('/<html\b/i', '<html data-og-canonical-lock="' . $lockAttr . '"', $out, 1) ?? $out;
        }
    }
    if ($canonicalHost !== '') {
        $hmeta = htmlspecialchars(strtolower($canonicalHost), ENT_QUOTES, 'UTF-8');
        $out = preg_replace_callback(
            '/<meta(\s[^>]*\bname\s*=\s*(["\'])og-origin-host\2[^>]*)>/i',
            static function (array $m) use ($hmeta): string {
                $inner = (string)($m[1] ?? '');
                $tag = '<meta' . $inner . '>';
                if (preg_match('/\bcontent\s*=\s*(["\'])/', $tag, $qm)) {
                    $q = $qm[1];

                    return (string)(preg_replace('/\bcontent\s*=\s*["\'][^"\']*["\']/', 'content=' . $q . $hmeta . $q, $tag, 1) ?? $tag);
                }

                return '<meta' . $inner . ' content="' . $hmeta . '">';
            },
            $out,
            1
        ) ?? $out;
    }

    $docPath = '/' . ltrim(str_replace('\\', '/', $rel), '/');
    if ($canonicalHost !== '' && !empty($autoProtectProfile['canonical_asset_urls'])) {
        $out = og_patch_canonicalize_html_urls($out, $canonicalHost, $docPath);
    }
    if ($canonicalHost !== '' && !empty($autoProtectProfile['neutralize_relative_urls'])) {
        $out = og_patch_neutralize_mirror_relative_refs($out);
    }
    if ($canonicalHost !== '' && !empty($autoProtectProfile['csp_frame_ancestors'])) {
        $out = og_patch_inject_copy_csp_meta($out, $canonicalHost);
    }
    if (!empty($autoProtectProfile['copy_defense_noscript'])) {
        $out = og_patch_inject_copy_noscript_harden($out);
    }

    if (!empty($OG_ASSET_MAP)) {
        $out = og_patch_apply_asset_ids($out, $OG_ASSET_MAP, strtolower($canonicalHost));
        // Inject MutationObserver that intercepts CSS/JS/images loaded dynamically from JS
        // (e.g. intl-tel-input, modals) — these are never HTML attributes and missed by apply_asset_ids.
        $ogDynObserverSnippet = og_patch_dyn_asset_observer_snippet($OG_ASSET_MAP);
        if ($ogDynObserverSnippet !== '') {
            $out = og_patch_safe_inject_before_head_close($out, $ogDynObserverSnippet);
        }
    }

    if (!$ENCRYPT_LANDING_BODY && $DEFER_LANDING_ASSETS) {
        $out = og_defer_landing_assets($out, $PATCH_PAYLOAD_KEY);
    }

    if ($ENCRYPT_LANDING_BODY && stripos($out, '<body') !== false) {
        $out = og_encrypt_landing_body($out, $PATCH_PAYLOAD_KEY, $canonicalHost, $ogKeySplit);
    }

    
    if (!$ENCRYPT_LANDING_BODY && stripos($out, '<body') !== false && !str_contains($out, 'og-content')) {
        $ogWrapStyle = $HIDE_OG_CONTENT_UNTIL_UNLOCK ? ' style="display:none;visibility:hidden"' : '';
        $out = preg_replace('/<body([^>]*)>/i',
            '<body$1><div id="og-content"' . $ogWrapStyle . '>', $out, 1);
        $out = og_patch_safe_replace_body_close($out, '</div></body>');
    } elseif (
        !$ENCRYPT_LANDING_BODY
        && !empty($autoProtectProfile['og_content_visible'])
        && preg_match('/<div\b[^>]*\bid\s*=\s*["\']og-content["\'][^>]*\bstyle\s*=\s*["\'][^"\']*display\s*:\s*none/i', $out)
    ) {
        $out = preg_replace(
            '/(<div\b[^>]*\bid\s*=\s*["\']og-content["\'][^>]*)\bstyle\s*=\s*["\'][^"\']*["\']/i',
            '$1',
            $out,
            1
        ) ?? $out;
    }

    $runScrapeHarden = $canonicalHost !== ''
        && stripos($out, '<body') !== false
        && ($autoProtectProfile === [] || !empty($autoProtectProfile['scrape_harden_og_content']));
    if ($runScrapeHarden && !$ENCRYPT_LANDING_BODY && str_contains($out, 'og-content')) {
        [$out, $scrapeOk] = og_patch_scrape_harden_og_content(
            $out,
            $PATCH_PAYLOAD_KEY,
            $canonicalHost,
            $ogKeySplit,
            $autoProtectFullSafe
        );
        if ($autoProtectFullSafe && !$scrapeOk) {
            fail('СТРОГИЙ РЕЖИМ — scrape_harden не зашифрував #og-content: ' . $rel);
            exit(1);
        }
    }

    
    if (
        !$ENCRYPT_LANDING_BODY
        && stripos($out, '</body>') !== false
        && !str_contains($out, '[OfferGuard:trap]')
        && ($autoProtectProfile === [] || !empty($autoProtectProfile['trap_links']))
    ) {
        $out = og_patch_safe_inject_before_body_close($out, $TRAP_HTML);
    }

    if ($canonicalHost !== '' && $autoProtectProfile !== []) {
        $out = og_patch_inject_early_copy_gate($out, $canonicalHost, $autoProtectProfile);
        if (stripos($out, '<body') !== false) {
            $out = og_patch_inject_body_copy_gate($out, $canonicalHost);
        }
        $out = OgFramework::applyHtmlCopyUx($out, $canonicalHost, $autoProtectProfile);
        $repairedOut = og_patch_repair_broken_offerguard_docopen_fragment($out, $canonicalHost, $autoProtectProfile);
        if ($repairedOut !== $out) {
            warn('[OfferGuard] repaired broken doc-open fragment in ' . $rel);
            $out = $repairedOut;
        }
    }

    $out = og_patch_safe_inject_before_body_close($out, $JS_DEPLOY_BLOCK);

    if ($canonicalHost !== '' && $autoProtectProfile !== []) {
        $out = og_patch_apply_copy_guard_config_to_html($out, $canonicalHost);
    }

    if ($canonicalHost !== '') {
        $out = og_patch_strip_static_origin_bypass($out);
        og_patch_verify_og_content_protected($out, $rel, $autoProtectFullSafe || $ogStrictProtect);
        if ($autoProtectFullSafe) {
            $lcEnsure = strtolower(trim($canonicalHost));
            if ($lcEnsure !== '') {
                $hesc = htmlspecialchars($lcEnsure, ENT_QUOTES, 'UTF-8');
                // КРИТИЧЕСКИ ВАЖНО: force-inject работает ТОЛЬКО на non-script регионах.
                // Регексы по `</head>` ловят литералы внутри JS-строк (`var b="...</head>..."`)
                // и разрушают JS. Поэтому режем HTML на script/non-script острова и инжектим
                // только в первую non-script часть с `</head>`.
                $ogInjectHead = static function (string $html, string $insertion) use (&$out): string {
                    $parts = preg_split('/(<script\b[^>]*>[\s\S]*?<\/script\s*>|<style\b[^>]*>[\s\S]*?<\/style\s*>)/i', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
                    if (!is_array($parts)) {
                        return $html;
                    }
                    foreach ($parts as $i => $chunk) {
                        if ($i % 2 === 1) {
                            continue;
                        }
                        if (preg_match('#</head\s*>#i', $chunk)) {
                            $parts[$i] = preg_replace('#</head\s*>#i', $insertion . "\n</head>", $chunk, 1) ?? $chunk;
                            return implode('', $parts);
                        }
                    }
                    return $html;
                };
                $ogHasNonScript = static function (string $html, string $needleRegex): bool {
                    $parts = preg_split('/(<script\b[^>]*>[\s\S]*?<\/script\s*>|<style\b[^>]*>[\s\S]*?<\/style\s*>)/i', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
                    if (!is_array($parts)) {
                        return preg_match($needleRegex, $html) === 1;
                    }
                    foreach ($parts as $i => $chunk) {
                        if ($i % 2 === 1) {
                            continue;
                        }
                        if (preg_match($needleRegex, $chunk)) {
                            return true;
                        }
                    }
                    return false;
                };
                if (!$ogHasNonScript($out, '/\bname\s*=\s*["\']og-origin-host["\'][^>]*\bcontent\s*=\s*["\']' . preg_quote($lcEnsure, '/') . '["\']/i')) {
                    $out = $ogInjectHead($out, '<meta name="og-origin-host" content="' . $hesc . '">');
                }
                // canonical-lock на <html> — здесь регекс на `<html\b` обычно безопасен
                // (литералов `<html` внутри JS-строк намного меньше), но всё одно через split.
                $parts = preg_split('/(<script\b[^>]*>[\s\S]*?<\/script\s*>|<style\b[^>]*>[\s\S]*?<\/style\s*>)/i', $out, -1, PREG_SPLIT_DELIM_CAPTURE);
                if (is_array($parts)) {
                    foreach ($parts as $i => $chunk) {
                        if ($i % 2 === 1) {
                            continue;
                        }
                        if (preg_match('/<html\b[^>]*>/i', $chunk, $hm) && stripos($hm[0], 'data-og-canonical-lock') === false) {
                            $parts[$i] = preg_replace('/<html\b/i', '<html data-og-canonical-lock="' . $hesc . '"', $chunk, 1) ?? $chunk;
                            $out = implode('', $parts);
                            break;
                        }
                    }
                }
                if (!$ogHasNonScript($out, '/http-equiv\s*=\s*["\']Content-Security-Policy["\']/i')) {
                    $csp = '<meta http-equiv="Content-Security-Policy" content="base-uri \'self\' https://' . $hesc . '; form-action \'self\' https://' . $hesc . ';">';
                    $out = $ogInjectHead($out, $csp);
                }
                if (!$ogHasNonScript($out, '/\[OfferGuard:noscript-harden\]/')) {
                    $ns = '<!-- [OfferGuard:noscript-harden] --><noscript><meta http-equiv="refresh" content="0;url=about:blank"><style>html,body{background:#fff!important}</style></noscript><!-- [/OfferGuard:noscript-harden] -->';
                    $out = $ogInjectHead($out, $ns);
                }
            }
            $relIsAncillary = og_patch_is_ancillary_html($rel);
            og_patch_log_protection_checklist(
                $rel,
                og_patch_protection_layer_audit(
                    $out,
                    $canonicalHost,
                    $autoProtectProfile,
                    $ENCRYPT_LANDING_BODY,
                    (bool)$ogEncryptJs,
                    $relIsAncillary
                ),
                !$relIsAncillary  // ancillary files: warn only, never abort
            );
        }
    }
    
    if ($ogStrictProtect && $ENCRYPT_LANDING_BODY) {
        og_patch_assert_landing_encrypted($out, $rel);
    }
    $out = og_patch_aggressive_strip_orphan_js($out);
    og_patch_self_test_guard_blocks($out, $rel);
    og_patch_validate_offerguard_script_balance($out, $rel);

    if (!$dryRun) { backup($p, $backupDir, $dryRun); file_put_contents($p, $out); }
    ok(($dryRun ? "[DRY] " : "") . ($hadOg ? "Обновлён: " : "Запатчен: ") . $rel);
    $hPatch++;
}
if (!$hPatch && !$hSkip) skip("HTML-файлов не найдено");

if (!$dryRun && $autoProtectFullSafe && $canonicalHost !== '') {
    OgFramework::verify($htmlFiles, $canonicalHost, $autoProtectProfile, $offerPath, $ENCRYPT_LANDING_BODY, (bool)$ogEncryptJs, true);
    OgFramework::writeAppliedLog($dataDir, $ogFrameworkDetect, $autoProtectProfile, $dryRun);
}




head("БЭКЕНД — PHP (опційно)");
$pPatch = $pSkip = 0;
$sSkip = 0;
$offerRootReal = realpath($offerPath) ?: $offerPath;
$ogPhpInjectTargets = og_patch_php_inject_targets($offerPath, $phpFiles, $ogCollectMeta, 5);
if ($ogPhpInject === null) {
    $ogPhpInject = !empty($autoProtectProfile['php_inject']);
    if (!$ogPhpInjectExplicit && $ogPhpInjectTargets !== [] && count($htmlFiles) > 0) {
        $ogPhpInject = false;
    }
}
if ($ogPhpInjectTargets !== []) {
    info('[OfferGuard] PHP inject targets (' . count($ogPhpInjectTargets) . '/5 max): '
        . implode(', ', array_map(static fn(string $x): string => str_replace($offerPath . '/', '', $x), $ogPhpInjectTargets)));
}
$ogSkipPhpInject = $ogPhpInjectTargets === [] && count($phpFiles) === 0 && empty($ogFrameworkDetect['has_index_php']);
if ($ogSkipPhpInject) {
    skip('Немає PHP html-outlet — Node/Python/Java/.NET/static: HTML + bot-protect.php');
    $pSkip = 1;
} elseif (!$ogPhpInject) {
    skip('PHP inject вимкнено (HTML + bot-protect достатньо). Увімкнути: --og-php-inject=1');
    $pSkip = count($ogPhpInjectTargets);
}
foreach ($ogPhpInject ? $ogPhpInjectTargets : [] as $p) {
    $rel = str_replace($offerPath . '/', '', $p);
    $src = file_get_contents($p);

    
    
    if ($ogEncryptPhp && $ENCRYPT_LANDING_BODY && $canonicalHost !== ''
        && (strcasecmp(basename($p), 'index.php') === 0 || og_patch_php_contains_html_output($src))) {
        $split = og_php_landing_split($src);
        $looksLanding = (stripos($src, '<body') !== false || stripos($src, '<html') !== false);
        if ($split === null) {
            
            
            
        } else {
            [$ogPhpProlog, $ogPhpHtml] = $split;
            $encHtml = og_encrypt_landing_body($ogPhpHtml, $PATCH_PAYLOAD_KEY, $canonicalHost, $ogKeySplit);
            $encHtml = og_patch_inject_early_copy_gate($encHtml, $canonicalHost, $autoProtectProfile);
            $encHtml = og_patch_inject_body_copy_gate($encHtml, $canonicalHost);
            $encHtml = OgFramework::applyHtmlCopyUx($encHtml, $canonicalHost, $autoProtectProfile);
            $encHtml = og_patch_repair_broken_offerguard_docopen_fragment($encHtml, $canonicalHost, $autoProtectProfile);
            $encHtml = og_patch_safe_inject_before_body_close($encHtml, $JS_DEPLOY_BLOCK);
            if ($autoProtectProfile !== []) {
                $encHtml = og_patch_apply_copy_guard_config_to_html($encHtml, $canonicalHost);
            }
            $encHtml = og_patch_strip_static_origin_bypass($encHtml);
            $out = $ogPhpProlog . $encHtml;
            og_patch_assert_landing_encrypted($out, $rel);
            $encHtml = og_patch_aggressive_strip_orphan_js($encHtml);
            og_patch_self_test_guard_blocks($encHtml, $rel . ' (embedded-html)');
            og_patch_validate_offerguard_script_balance($encHtml, $rel . ' (embedded-html)');
            if (!$dryRun) { backup($p, $backupDir, $dryRun); file_put_contents($p, $out); }
            ok(($dryRun ? "[DRY] " : "") . 'Зашифрован .php-лендинг (тело + defer-ассеты): ' . $rel);
            $pPatch++;
            continue;
        }
    }

    $hadOg = str_contains($src, '[OfferGuard:start]');
    $out = inject_php_guard(og_strip_php_offer_guard_blocks($src), $PHP_INJECT_RUNTIME);

    if (!$hadOg && !str_contains($src, 'og_str')) {
        $hasBracedNamespace = preg_match('/^\s*<\?php[\s\S]*?\bnamespace\s+[^;{]+\s*\{/i', $src) === 1;
        $trimmed = rtrim($out);
        $closeTag = '?' . '>'; 
        if ($hasBracedNamespace) {
            
            $sSkip++;
        } elseif (str_ends_with($trimmed, $closeTag)) {
            $out = substr($trimmed, 0, -2) . $SANITIZE;
        } elseif (strpos($out, '?>') === false) {
            $out .= $SANITIZE;
        } else {
            
            $sSkip++;
        }
    }
    $out = og_patch_aggressive_strip_orphan_js($out);
    og_patch_self_test_guard_blocks($out, $rel);
    og_patch_validate_offerguard_script_balance($out, $rel);

    if (!$dryRun) { backup($p, $backupDir, $dryRun); file_put_contents($p, $out); }
    ok(($dryRun ? "[DRY] " : "") . ($hadOg ? "Обновлён: " : "Запатчен: ") . $rel);
    $pPatch++;
}
if (!$pPatch && !$pSkip && !$ogSkipPhpInject) skip("PHP-файлов не найдено");




head("СИСТЕМНЫЕ ФАЙЛЫ");

if ($ogKeySplit) {
    info(($dryRun ? '[DRY] ' : '') . 'Split-key' . ($ogAggressiveMax ? ' (max-aggressive)' : '')
        . ': статика под K=HMAC(KfragBoot|0|nonce,Kbase); kf TTL 8s; hook=origin bot-protect (SSE+poll).');
    if (!$ogEncryptBody) {
        warn('--og-key-split=1 без --og-encrypt-body=1 — split защищает только зашифрованный путь; включите encrypt body.');
    }
    if (strtolower(trim($canonicalHost)) === '') {
        warn('Split-key требует --canonical-host= (Kbase привязан к хосту) — без него split не активируется.');
    }
}

if (!$dryRun) {
    $botContent = str_replace(
        ['OG_SECRET_CHANGE_ME', 'OG_PAYLOAD_KEY_CHANGE_ME', 'OG_CANONICAL_HOST_CHANGE_ME', 'OG_JSK_EP_CHANGE_ME', '__OG_ASSETS_SUBDIR__', '__OG_SECRET_PATH__'],
        [$PATCH_COOKIE_SECRET, $PATCH_PAYLOAD_KEY, strtolower($canonicalHost), $ogJskEp, trim($ogAssetsSubdir, '/'), $ogSecretPath],
        $BOT_PROTECT
    );
    $botContent = og_patch_apply_bot_webhook_config($botContent, $ogWebhookMode, $ogWebhookUrlPatch);
    $botContent = og_patch_apply_key_split_config($botContent, $ogKeySplit, $ogAggressiveMax);
    if ($autoProtectFullSafe) {
        $botContent = og_patch_apply_bot_auto_protect_profile($botContent, $autoProtectProfile);
        $botContent = og_patch_apply_bot_live_pub_config($botContent);
        $botContent = og_patch_apply_live_hook_tab_config($botContent);
    } else {
        $botContent = og_patch_apply_reload_grace_config($botContent);
    }
    $botContent = og_patch_harden_bot_protect_php($botContent);
    if ($ogWebhookMode === 'authoritative' && $ogWebhookUrlPatch === '') {
        warn('--og-webhook-mode=authoritative без OG_WEBHOOK_URL — у bot-protect залишиться notify (runtime downgrade).');
    }
    $ogLintTmp = sys_get_temp_dir() . '/og-bot-protect-lint-' . getmypid() . '.php';
    if (@file_put_contents($ogLintTmp, $botContent) !== false) {
        exec('php -l ' . escapeshellarg($ogLintTmp) . ' 2>&1', $ogLintOut, $ogLintCode);
        @unlink($ogLintTmp);
        if ($ogLintCode !== 0) {
            warn('bot-protect.php не прошёл php -l: ' . trim(implode(' ', $ogLintOut)));
        }
    }
    if (is_file($protectDest)) {
        backup($protectDest, $backupDir, $dryRun);
    }
    file_put_contents($protectDest, $botContent);
    ok("Записан: bot-protect.php");
    $ogPatchPerm = og_patch_fix_og_data_writable($offerPath, og_patch_resolve_web_owner($offerPath));
    if ($ogPatchPerm['fixed']) {
        ok('_og_data: ' . $ogPatchPerm['path'] . ' (' . implode(', ', $ogPatchPerm['notes']) . ')');
    } else {
        warn('_og_data не writable — sudo php patch.php ' . $offerPath . ' (або --site-up)');
    }
    // Авто-додаємо localhost у whitelist щоб doctor-curl та сервер-side тести
    // (через cron/health-checks) не банилися. SSH IP можна дописати юзером через --allow.
    $ogPatchWl = $dataDir . '/whitelist.txt';
    $ogPatchWlList = is_file($ogPatchWl)
        ? (file($ogPatchWl, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [])
        : [];
    $ogPatchWlList = array_map('trim', $ogPatchWlList);
    foreach (['127.0.0.1', '::1'] as $ogPatchLocalIp) {
        if (!in_array($ogPatchLocalIp, $ogPatchWlList, true)) {
            $ogPatchWlList[] = $ogPatchLocalIp;
        }
    }
    // SSH IP — спроба авто-визначення
    $ogPatchSsh = getenv('SSH_CONNECTION');
    if (is_string($ogPatchSsh) && trim($ogPatchSsh) !== '') {
        $ogPatchSshParts = preg_split('/\s+/', trim($ogPatchSsh));
        $ogPatchSshIp = $ogPatchSshParts[0] ?? '';
        if (filter_var($ogPatchSshIp, FILTER_VALIDATE_IP)
            && !in_array($ogPatchSshIp, $ogPatchWlList, true)) {
            $ogPatchWlList[] = $ogPatchSshIp;
            info('[OfferGuard] SSH IP ' . $ogPatchSshIp . ' додано у whitelist (можна прибрати: --deny ' . $ogPatchSshIp . ').');
        }
    }
    @file_put_contents($ogPatchWl, implode("\n", $ogPatchWlList) . "\n", LOCK_EX);
} else ok("[DRY] Будет записан: bot-protect.php");

$rFile = $offerPath . '/robots.txt';
if (!file_exists($rFile)) {
    if (!$dryRun) file_put_contents($rFile, $ROBOTS);
    ok(($dryRun ? "[DRY] " : "") . "Создан: robots.txt");
} else warn("robots.txt уже существует — не трогаем");

$htFile = $offerPath . '/.htaccess';
$GLOBALS['OG_PATCH_HTACCESS_FULL'] = $HTACCESS;
if (!$ogWriteHtaccessExplicit) {
    $ogWriteHtaccess = !empty($autoProtectProfile['htaccess_write']);
}
$GLOBALS['OG_PATCH_WRITE_HTACCESS'] = $ogWriteHtaccess;
$GLOBALS['OG_PATCH_HTACCESS_FULL_CREATE'] = in_array('--og-htaccess-full=1', $args, true);
if (!array_key_exists('OG_PATCH_HTACCESS_SAFE', $GLOBALS)) {
    $GLOBALS['OG_PATCH_HTACCESS_SAFE'] = !empty($autoProtectProfile['htaccess_safe_mode']);
}
if (!$ogWriteHtaccess) {
    ok('.htaccess НЕ чіпаємо (адаптивний pure-PHP режим).');
    info('JS-гард б\'є напряму в `/bot-protect.php?_og_ep=…` (працює без mod_rewrite).');
    info('Хочеш clean URLs `/_site/{v,r,s}` та mirror-probe trap → `--og-htaccess=1`.');
} elseif (!file_exists($htFile)) {
    og_patch_write_universal_htaccess_rules($htFile, $dryRun, true);
    $htMode = !empty($GLOBALS['OG_PATCH_HTACCESS_SAFE']) ? 'safe /_site/* + mirror-probe' : 'full runtime';
    ok(($dryRun ? "[DRY] " : "") . "Создан: .htaccess ({$htMode} → bot-protect)");
} else {
    if (!$dryRun) {
        backup($htFile, $backupDir, $dryRun);
    }
    og_patch_write_universal_htaccess_rules($htFile, $dryRun, false);
    $htMode = !empty($GLOBALS['OG_PATCH_HTACCESS_SAFE']) ? 'safe universal runtime' : 'full universal runtime';
    ok(($dryRun ? "[DRY] " : "") . "Обновлён: .htaccess (merged OfferGuard {$htMode})");
}




head("ИТОГ");
$total = $hPatch + $pPatch;
out(GREEN . "  HTML запатчено:   $hPatch  " . GRAY . "(пропущено: $hSkip)");
out(GREEN . "  PHP запатчено:    $pPatch  " . GRAY . "(пропущено: $pSkip)");
out(GREEN . "  SANITIZE пропущен:" . " $sSkip  " . GRAY . "(смешанный PHP/HTML)");
out(GREEN . "  Всего:            $total файл(ов)");
if (!$dryRun) {
    out(CYAN . "  Бэкапы:           $backupDir");
    out("");
    out(B . GREEN . "  ✓ Защита v6 вшита (максимальный режим).");
    if ($autoProtectFullSafe && $autoProtectProfile !== []) {
        out(CYAN . '  Профіль:   ' . og_patch_summarize_auto_protect($autoProtectProfile, $canonicalHost));
    }
    out(YEL  . "  Откат:     php patch.php $offerPath --rollback");
    out(YEL  . "  Очистка:   php patch.php $offerPath --cleanup");
    out(YEL  . "  Origin-safe re-patch: php patch.php $offerPath --canonical-host=$canonicalHost --og-origin-safe");
    out(YEL  . "  Apache UP: php patch.php $offerPath --og-emergency-unbreak");
    out(GRAY . "  Re-patch:  php patch.php $offerPath --canonical-host=...  (pure PHP, без .htaccess за замовч.)");
    out(CYAN . "  Статус:    php patch.php $offerPath --status");
    out(CYAN . "  Баны:      php patch.php $offerPath --bans");
    out(CYAN . "  Вайтлист:  php patch.php $offerPath --whitelist");
    out(CYAN . "  Разбанить: php patch.php $offerPath --unban <IP>");
    out(CYAN . "  Разрешить: php patch.php $offerPath --allow <IP>");
}
out("");