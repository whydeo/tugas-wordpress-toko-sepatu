<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

if (!defined('ABSPATH')) exit;


use MailPoet\EmailEditor\Engine\Renderer\BlockRenderer;
use MailPoet\EmailEditor\Engine\SettingsController;
use MailPoet\Util\Helpers;

class Heading implements BlockRenderer {
  public function render($blockContent, array $parsedBlock, SettingsController $settingsController): string {
    $contentStyles = $settingsController->getEmailContentStyles();
    $availableStylesheets = $settingsController->getAvailableStylesheets();
    return str_replace('{heading_content}', $blockContent, $this->getBlockWrapper($parsedBlock, $contentStyles, $availableStylesheets));
  }

  /**
   * Based on MJML <mj-text>
   */
  private function getBlockWrapper(array $parsedBlock, array $contentStyles, ?string $availableStylesheets): string {
    $styles = [];
    foreach ($parsedBlock['email_attrs'] ?? [] as $property => $value) {
      $styles[$property] = $value;
    }

    if (!isset($styles['font-size'])) {
      $styles['font-size'] = $contentStyles['typography']['fontSize'];
    }
    if (!isset($styles['font-family'])) {
      $styles['font-family'] = $contentStyles['typography']['fontFamily'];
    }

    $styles = array_merge($styles, $this->fetchStylesFromBlockAttrs($availableStylesheets, $parsedBlock['attrs']));

    return '
      <table
        role="presentation"
        border="0"
        cellpadding="0"
        cellspacing="0"
        style="' . $this->convertStylesToString($styles) . '"
      >
        <tr>
          <td>
            {heading_content}
          </td>
        </tr>
      </table>
    ';
  }

  private function convertStylesToString(array $styles): string {
    $cssString = '';
    foreach ($styles as $property => $value) {
      $cssString .= $property . ':' . $value . '; ';
    }
    return trim($cssString); // Remove trailing space and return the formatted string
  }

  private function fetchStylesFromBlockAttrs(?string $availableStylesheets, array $attrs = []): array {
    $styles = [];

    $supportedValues = ['textAlign'];

    foreach ($supportedValues as $supportedValue) {
      if (array_key_exists($supportedValue, $attrs)) {
        $styles[Helpers::camelCaseToKebabCase($supportedValue)] = $attrs[$supportedValue];
      }
    }

    // using custom rules because colors do not automatically resolve to hex value
    $supportedColorValues = ['backgroundColor', 'textColor'];
    foreach ($supportedColorValues as $supportedColorValue) {
      if (array_key_exists($supportedColorValue, $attrs)) {
        $colorKey = $attrs[$supportedColorValue];

        $cssString = $availableStylesheets ?? '';

        $colorRegex = "/--wp--preset--color--$colorKey: (#[0-9a-fA-F]{6});/";

        // fetch color hex from available stylesheets
        preg_match($colorRegex, $cssString, $colorMatch);

        $colorValue = '';
        if ($colorMatch) {
          $colorValue = $colorMatch[1];
        }

        if ($supportedColorValue === 'textColor') {
          $styles['color'] = $colorValue; // use color instead of textColor. textColor not valid CSS property
        } else {
          $styles[Helpers::camelCaseToKebabCase($supportedColorValue)] = $colorValue;
        }

      }
    }

    // fetch Block Style Typography e.g., fontStyle, fontWeight, etc
    if (isset($attrs['style']['typography'])) {
      $blockStyleTypographyKeys = array_keys($attrs['style']['typography']);
      foreach ($blockStyleTypographyKeys as $blockStyleTypographyKey) {
        $styles[Helpers::camelCaseToKebabCase($blockStyleTypographyKey)] = $attrs['style']['typography'][$blockStyleTypographyKey];
      }
    }

    return $styles;
  }
}
