<?php

$markdown = file_get_contents("markdown.txt");
$lines = explode("\n", $markdown);
$processed_markdown = parse($lines);
file_put_contents("output.html", $processed_markdown);

function parse($lines)
{
    // Return if empty file.
    if (empty($lines)) {
        return false;
    }

    $pointer = -1;
    $markup = '';

    // Iterate through each line and append to $markup as necessary.
    while (isset($lines[++$pointer])) {

        $line = trim($lines[$pointer]);
        // Check to see if there is a next line.
        if (isset($lines[$pointer + 1])) {
            $next_line = trim($lines[$pointer + 1]);
        }
        if ($pointer >= 1) {
            $previous_line = trim($lines[$pointer - 1]);
        }

        /* Blank Line */

        if (empty($line)) {
            continue;
        }
        elseif (!empty($line)) {

            /* Header */

            $char_count = header_test($line);
            if ($char_count && $char_count <= 6) {
                $hash_level = $char_count;
                $pre_markup = "<h$hash_level>" . ltrim($line, '# ') . "</h$hash_level> \n";
                // Links in headers.
                $markup .= process_link($pre_markup);
                continue;
            }
            if ($char_count && $char_count > 6) {
                $text_line = $pointer + 1;
                return "Line $text_line: Headers over h6 not supported";
            }

            /* Unformatted Text */

            // First look for links in unformatted text.
            $new_line = process_link($line);

            // Paragraph states.
            $start_paragraph = start_paragraph($previous_line, $next_line);
            $end_paragraph = end_paragraph($previous_line, $next_line);
            $middle_paragraph = middle_paragraph($previous_line, $next_line);
            $isolated_paragraph = isolated_paragraph($previous_line, $next_line);
            //print "Line: $line \n start paragraph: $start_paragraph \n end paragraph: $end_paragraph\n isolated paragraph: $isolated_paragraph\n middle paragraph: $middle_paragraph\n";

            if ($start_paragraph) {
                $new_line = "<p>" . $new_line . ' ';
                $markup .= $new_line . "\n";
            }
            elseif ($end_paragraph) {
                $new_line = $new_line . "</p>";
                $markup .= $new_line . "\n";
            }
            elseif ($middle_paragraph) {
                $markup .= $new_line . ' ' . "\n";
            }
            elseif ($isolated_paragraph) {
                // Don't continue the paragraph.
                $new_line = "<p>" . $new_line . "</p>";
                $markup .= $new_line . "\n";
            }

        }

    }

    return $markup;
}


/* Formatting Tests and Processes */

function header_test($line)
{
    $char1 = $line[0];
    if ($char1 === '#') {
        $level = 0;
        $line_array = str_split($line);
        $hash_level = _count_char($line_array, $char1, $level);
        // TODO make this less tricky, returns FALSE if not a header and a number representing the hash level if a header.
        return $hash_level;
    }
}

function process_link($line)
{
    $pos = strpos($line, '[');
    // See if we need to process.
    if ($pos !== FALSE) {
        // Replace if all the parts and pieces are there.
        $regex = '/\[([^\[]+)\]\(([^\)]+)\)/';
        $replace = '<a href=\'\2\'>\1</a>';
        $new_line = preg_replace($regex, $replace, $line);
    } else {
        $new_line = $line;
    }
    return $new_line;
}

function start_paragraph($previous_line, $next_line)
{
    if (empty($next_line)) {
        return FALSE;
    } // Evaluates to 0 if not a header
    elseif (header_test($next_line)) {
        // If header test returns true, return false for continuing paragraph.
        return FALSE;
    } elseif (_is_paragraph($previous_line)) {
        return FALSE;
    } else {
        return TRUE;
    }
}

function end_paragraph($previous_line, $next_line)
{
    if (_is_paragraph($previous_line) === TRUE && start_paragraph($previous_line, $next_line) === FALSE && middle_paragraph($previous_line, $next_line) !== TRUE) {
        return TRUE;
    } else {
        return FALSE;
    }
}

function middle_paragraph($previous_line, $next_line)
{
    if (_is_paragraph($previous_line) && _is_paragraph($next_line)) {
        return TRUE;
    } else {
        return FALSE;
    }

}

function isolated_paragraph($previous_line, $next_line)
{
    if ((header_test($previous_line) || empty($previous_line)) && (header_test($next_line) || empty($next_line))) {
        return TRUE;
    } else {
        return FALSE;
    }
}

// Abstracting so we can use this later with other types of characters.
function _count_char($line_array, $char, $level)
{
    foreach ($line_array as $character) {
        if ($character === $char) {
            $level++;
        } elseif ($character !== $char && $character !== ' ') {
            $level = FALSE;
            return $level;
        } else {
            break;
        }
    }
    return $level;
}

function _is_paragraph($line)
{
    if (empty($line)) {
        return FALSE;
    }
    if (header_test($line)) {
        return FALSE;
    }
    return TRUE;
}


?>
