<?php

/*
 * This file is part of the vSymfo package.
 *
 * website: www.vision-web.pl
 * (c) Rafał Mikołajun <rafal@vision-web.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace vSymfo\Component\Document\Format;

use Stringy\Stringy as S;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;
use vSymfo\Component\Document\Element\HtmlElement;

/**
 * Dokument RSS
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Type
 */
class RssDocument extends XmlDocument
{
    /**
     * @var HtmlElement
     */
    private $channel = null;

    /**
     * @var HtmlElement
     */
    private $title = null;

    /**
     * @var HtmlElement
     */
    private $link = null;

    /**
     * @var HtmlElement
     */
    private $description = null;

    /**
     * @var HtmlElement
     */
    private $language = null;

    /**
     * @var HtmlElement
     */
    private $copyright = null;

    /**
     * @var HtmlElement
     */
    private $managingEditor = null;

    /**
     * @var HtmlElement
     */
    private $webMaster = null;

    /**
     * @var HtmlElement
     */
    private $pubDate = null;

    /**
     * @var HtmlElement
     */
    private $lastBuildDate = null;

    /**
     * @var HtmlElement
     */
    private $category = null;

    /**
     * @var HtmlElement
     */
    private $generator = null;

    /**
     * @var HtmlElement
     */
    private $ttl = null;

    /**
     * @var OptionsResolver
     */
    private $imageResolver = null;

    /**
     * @var HtmlElement
     */
    private $image = null;

    /**
     * @var HtmlElement
     */
    private $imageUrl = null;

    /**
     * @var HtmlElement
     */
    private $imageTitle = null;

    /**
     * @var HtmlElement
     */
    private $imageLink = null;

    /**
     * @var HtmlElement
     */
    private $imageWidth = null;

    /**
     * @var HtmlElement
     */
    private $imageHeight = null;

    /**
     * @var HtmlElement
     */
    private $imageDescription = null;

    /**
     * @var OptionsResolver
     */
    private $textInputResolver = null;

    /**
     * @var HtmlElement
     */
    private $textInput = null;

    /**
     * @var HtmlElement
     */
    private $textInputTitle = null;

    /**
     * @var HtmlElement
     */
    private $textInputDescription = null;

    /**
     * @var HtmlElement
     */
    private $textInputName = null;

    /**
     * @var HtmlElement
     */
    private $textInputLink = null;

    /**
     * @var HtmlElement
     */
    private $skipHours = null;

    /**
     * @var HtmlElement
     */
    private $skipDays = null;

    /**
     * @var OptionsResolver
     */
    private $cloudResolver = null;

    /**
     * @var HtmlElement
     */
    private $cloud = null;

    public function __construct()
    {
        parent::__construct();
        // zmiana elementu root
        $this->root->destroy($this->root);
        $this->root = new HtmlElement('rss');
        $this->root->attr('version', '2.0');

        // element channel
        $this->channel = new HtmlElement('channel');
        $this->channel->insertTo($this->root);

        // elementy zawarte w channel
        $this->title = new HtmlElement('title');
        $this->link = new HtmlElement('link');
        $this->description = new HtmlElement('description');
        $this->language = new HtmlElement('language');
        $this->copyright = new HtmlElement('copyright');
        $this->managingEditor = new HtmlElement('managingEditor');
        $this->webMaster = new HtmlElement('webMaster');
        $this->pubDate = new HtmlElement('pubDate');
        $this->lastBuildDate = new HtmlElement('lastBuildDate');
        $this->category = new HtmlElement('category');
        $this->generator = new HtmlElement('generator');
        $this->ttl = new HtmlElement('ttl');
        $this->skipHours = new HtmlElement('skipHours');
        $this->skipDays = new HtmlElement('skipDays');
        $this->cloud = new HtmlElement('cloud');

        $this->image = new HtmlElement('image');
        $this->imageUrl = new HtmlElement('url');
        $this->imageUrl->insertTo($this->image);
        $this->imageTitle = new HtmlElement('title');
        $this->imageTitle->insertTo($this->image);
        $this->imageLink = new HtmlElement('link');
        $this->imageLink->insertTo($this->image);
        $this->imageWidth = new HtmlElement('width');
        $this->imageHeight = new HtmlElement('height');
        $this->imageDescription = new HtmlElement('description');

        $this->textInput = new HtmlElement('textInput');
        $this->textInputTitle = new HtmlElement('title');
        $this->textInputTitle->insertTo($this->textInput);
        $this->textInputDescription = new HtmlElement('description');
        $this->textInputDescription->insertTo($this->textInput);
        $this->textInputName = new HtmlElement('name');
        $this->textInputName->insertTo($this->textInput);
        $this->textInputLink = new HtmlElement('link');
        $this->textInputLink->insertTo($this->textInput);

        // konfiguracja niektórych elementów
        $this->setImageResolver();
        $this->setTextInputResolver();
        $this->setCloudResolver();
    }

    /**
     * {@inheritdoc}
     */
    public function formatName()
    {
        return "rss";
    }

    /**
     * {@inheritdoc}
     */
    public function renameRoot($name)
    {
        throw new \RuntimeException('Operation not allowed. You can not change RSS root element.');
    }

    /**
     * Filtr tekstowy.
     * Wszystkie znaki w jednej linii + htmlspecialchars().
     * @param HtmlElement $htmlElement
     * @param string $text
     */
    private function filterText(HtmlElement $htmlElement, $text)
    {
        $htmlElement->text(
            htmlspecialchars(
                S::create($text)->collapseWhitespace()
                , ENT_QUOTES
                , $this->encoding->render()
            )
        );
    }

    /**
     * Filtr na adres URL.
     * @param HtmlElement $htmlElement
     * @param string $text
     */
    private function filterLink(HtmlElement $htmlElement, $text)
    {
        $htmlElement->text(
            filter_var($text, FILTER_VALIDATE_URL)
                ? $text
                : ''
        );
    }

    /**
     * Filtr na prawidłową datę.
     * @param HtmlElement $htmlElement
     * @param string $text
     * @throws \UnexpectedValueException
     */
    private function filterDate(HtmlElement $htmlElement, $text)
    {
        $date = \DateTime::createFromFormat(\DateTime::RSS, $text);
        if ($date === false) {
            throw new \UnexpectedValueException("Invalid RSS date format. Must be: " . \DateTime::RSS);
        }

        $htmlElement->text($date->format(\DateTime::RSS));
    }

    /**
     * Tworzenie obiektu do weryfikacji tablicy
     * z danymi wejściowymi elementu 'image'
     */
    private function setImageResolver()
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired(array('url', 'title', 'link'));

        $resolver->setDefaults(array(
                'description' => '',
                'width' => 0,
                'height' => 0
            ));

        $resolver->setAllowedTypes(array(
                'url' => 'string',
                'title' => 'string',
                'link' => 'string',
                'description' => 'string',
                'width' => 'integer',
                'height' => 'integer'
            ));

        $resolver->setNormalizers(array(
                'width' => function (Options $options, $value) {
                        if (is_int($value)) {
                            if ($value > 144) {
                                $value = 144;
                            } elseif ($value < 0) {
                                $value = 0;
                            }
                        }

                        return $value;
                    },
                'height' => function (Options $options, $value) {
                        if (is_int($value)) {
                            if ($value > 400) {
                                $value = 400;
                            } elseif ($value < 0) {
                                $value = 0;
                            }
                        }

                        return $value;
                    },
            ));

        $this->imageResolver = $resolver;
    }

    /**
     * Tworzenie obiektu do weryfikacji tablicy
     * z danymi wejściowymi elementu 'textInput'
     */
    private function setTextInputResolver()
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired(array('title', 'link', 'description', 'name'));

        $resolver->setAllowedTypes(array(
                'title' => 'string',
                'link' => 'string',
                'description' => 'string',
                'name' => 'string'
            ));

        $this->textInputResolver = $resolver;
    }

    /**
     * Tworzenie obiektu do weryfikacji tablicy
     * z danymi wejściowymi elementu 'cloud'
     */
    private function setCloudResolver()
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired(array('domain', 'port', 'path', 'registerProcedure', 'protocol'));

        $resolver->setAllowedTypes(array(
                'domain' => 'string',
                'port' => 'integer',
                'path' => 'string',
                'registerProcedure' => 'string',
                'protocol' => 'string'
            ));

        $this->cloudResolver = $resolver;
    }


    /**
     * {@inheritdoc}
     */
    public function title($set = null, $mode = self::TITLE_ONLY_TITLE, $separator = '-')
    {
        $title = parent::title($set, $mode, $separator);
        $this->title->text(htmlspecialchars($title, ENT_QUOTES, $this->encoding->render()));
        return $title;
    }

    /**
     * {@inheritdoc}
     */
    public function description($set = null)
    {
        $desc = parent::description($set);
        $this->description->text(htmlspecialchars($desc, ENT_QUOTES, $this->encoding->render()));

        return $desc;
    }

    /**
     * Link do serwisu
     * @param string $set
     * @return string
     */
    public function link($set = null)
    {
        if (is_string($set)) {
            $this->filterLink($this->link, $set);
        }

        return $this->link->text();
    }

    /**
     * Język kanału
     * @param string $set
     * @return string
     */
    public function language($set = null)
    {
        if (is_string($set)) {
            $this->language->text(preg_replace('/[^A-Z0-9 _\.-]/i', '', $set));
        }

        return $this->language->text();
    }

    /**
     * Prawa autorskie
     * @param string $set
     * @return string
     */
    public function copyright($set = null)
    {
        if (is_string($set)) {
            $this->filterText($this->copyright, $set);
        }

        return $this->copyright->text();
    }

    /**
     * Kontakt z redaktorem
     * @param string $set
     * @return string
     */
    public function managingEditor($set = null)
    {
        if (is_string($set)) {
            $this->filterText($this->managingEditor, $set);
        }

        return $this->managingEditor->text();
    }

    /**
     * Kontakt z webmasterem
     * @param string $set
     * @return string
     */
    public function webMaster($set = null)
    {
        if (is_string($set)) {
            $this->filterText($this->webMaster, $set);
        }

        return $this->webMaster->text();
    }

    /**
     * Data opublikowania treści
     * @param string $set
     * @return string
     * @throws \UnexpectedValueException
     */
    public function pubDate($set = null)
    {
        if (is_string($set)) {
            $this->filterDate($this->pubDate, $set);
        }

        return $this->pubDate->text();
    }

    /**
     * Data ostatniej zmiany
     * @param string $set
     * @return string
     * @throws \UnexpectedValueException
     */
    public function lastBuildDate($set = null)
    {
        if (is_string($set)) {
            $this->filterDate($this->lastBuildDate, $set);
        }

        return $this->lastBuildDate->text();
    }

    /**
     * Kategoria kanału
     * @param string $set
     * @return string
     */
    public function category($set = null)
    {
        if (is_string($set)) {
            $this->filterText($this->category, $set);
        }

        return $this->category->text();
    }

    /**
     * Generator (program generujący) kanału
     * @param string $set
     * @return string
     */
    public function generator($set = null)
    {
        if (is_string($set)) {
            $this->filterText($this->generator, $set);
        }

        return $this->generator->text();
    }

    /**
     * Jak długo wpisy mają być w cache'u czytnika.
     * W minutach.
     * @param string $set
     * @return string
     */
    public function ttl($set = null)
    {
        if (is_string($set)) {
            $this->ttl->text((string)intval($set));
        }

        return $this->ttl->text();
    }

    /**
     * Ilustracja kanału
     * @param array $set
     * @return array
     */
    public function image(array $set = null)
    {
        if (is_array($set)) {
            $options = $this->imageResolver->resolve($set);
            $this->filterLink($this->imageUrl, $options['url']);
            $this->filterText($this->imageTitle, $options['title']);
            $this->filterLink($this->imageLink, $options['link']);

            if (!empty($options['description'])) {
                $this->filterText($this->imageDescription, $options['description']);
                $this->imageDescription->insertTo($this->image);
            } else {
                $this->imageDescription->detach();
            }

            if (!empty($options['width'])) {
                $this->imageWidth
                    ->text((string)intval($options['width']))
                    ->insertTo($this->image)
                ;
            } else {
                $this->imageWidth->detach();
            }

            if (!empty($options['height'])) {
                $this->imageHeight
                    ->text((string)intval($options['height']))
                    ->insertTo($this->image)
                ;
            } else {
                $this->imageHeight->detach();
            }
        }

        return array(
            'url' => $this->imageUrl->text(),
            'title' => $this->imageTitle->text(),
            'link' => $this->imageLink->text(),
            'description' => $this->imageDescription->text(),
            'width' => $this->imageWidth->text(),
            'height' => $this->imageHeight->text()
        );
    }

    /**
     * Pole tekstowe na kanale
     * @param array $set
     * @return array
     */
    public function textInput(array $set = null)
    {
        if (is_array($set)) {
            $options = $this->textInputResolver->resolve($set);
            $this->filterText($this->textInputTitle, $options['title']);
            $this->filterLink($this->textInputLink, $options['link']);
            $this->filterText($this->textInputDescription, $options['description']);
            $this->filterText($this->textInputName, $options['name']);
        }

        return array(
            'title' => $this->textInputTitle->text(),
            'link' => $this->textInputLink->text(),
            'description' => $this->textInputDescription->text(),
            'name' => $this->textInputName->text()
        );
    }

    /**
     * Pomiń godziny
     * @param array $set
     * @return array
     */
    public function skipHours(array $set = null)
    {
        if (is_array($set)) {
            // czyszczenie
            foreach ($this->skipHours->xpath('hour') as $hour) {
                $hour->parentNode->removeChild($hour);
                unset($hour);
            }

            // wstawianie nowych godzin
            foreach ($set as $hour) {
                $num = intval($hour);
                if ($num >= 0 && $num <= 23) {
                    $el = new HtmlElement('hour');
                    $el->text((string)$num);
                    $el->insertTo($this->skipHours);
                }
            }
        }

        $hours = array();
        foreach ($this->skipHours->xpath('hour') as $hour) {
            $hours[] = $hour->nodeValue;
        }

        return $hours;
    }

    /**
     * Pomiń dni
     * @param array $set
     * @return array
     */
    public function skipDays(array $set = null)
    {
        if (is_array($set)) {
            // czyszczenie
            foreach ($this->skipDays->xpath('day') as $day) {
                $day->parentNode->removeChild($day);
                unset($day);
            }

            // wstawianie nowych godzin
            $allowed = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
            foreach ($set as $day) {
                if (is_string($day)) {
                    $day = ucfirst(strtolower($day));
                    if (in_array($day, $allowed)) {
                        $el = new HtmlElement('day');
                        $el->text((string)$day);
                        $el->insertTo($this->skipDays);
                    }
                }
            }
        }

        $days = array();
        foreach ($this->skipDays->xpath('day') as $day) {
            $days[] = $day->nodeValue;
        }

        return $days;
    }

    /**
     * Chmura RSS
     * @param array $set
     * @return array
     */
    public function cloud(array $set = null)
    {
        if (is_array($set)) {
            $options = $this->cloudResolver->resolve($set);
            $this->cloud->attr('domain', htmlspecialchars($options['domain'], ENT_QUOTES, $this->encoding->render()));
            $this->cloud->attr('port', (string)$options['port']);
            $this->cloud->attr('path', htmlspecialchars($options['path'], ENT_QUOTES, $this->encoding->render()));
            $this->cloud->attr('registerProcedure', htmlspecialchars($options['registerProcedure'], ENT_QUOTES, $this->encoding->render()));
            $this->cloud->attr('protocol', htmlspecialchars($options['protocol'], ENT_QUOTES, $this->encoding->render()));
        }

        return array(
            'domain' => $this->cloud->attr('domain'),
            'port' => $this->cloud->attr('port'),
            'path' => $this->cloud->attr('path'),
            'registerProcedure' => $this->cloud->attr('registerProcedure'),
            'protocol' => $this->cloud->attr('protocol')
        );
    }

    /**
     * Zawartość znacznika channel
     * @return string
     */
    private function channelContent()
    {
        $output = '';
        $output .= $this->title->render();
        $output .= '<link>' . $this->link->text(). '</link>';
        $output .= $this->description->render();
        if (!$this->language->isEmpty()) $output .= $this->language->render();
        if (!$this->copyright->isEmpty()) $output .= $this->copyright->render();
        if (!$this->managingEditor->isEmpty()) $output .= $this->managingEditor->render();
        if (!$this->webMaster->isEmpty()) $output .= $this->webMaster->render();
        if (!$this->pubDate->isEmpty()) $output .= $this->pubDate->render();
        if (!$this->lastBuildDate->isEmpty()) $output .= $this->lastBuildDate->render();
        if (!$this->category->isEmpty()) $output .= $this->category->render();
        if (!$this->generator->isEmpty()) $output .= $this->generator->render();
        if (!$this->ttl->isEmpty()) $output .= $this->ttl->render();
        if (!$this->imageUrl->isEmpty() && !$this->imageTitle->isEmpty() && !$this->imageLink->isEmpty()) {
            $output .= str_replace(
                "<link>", "<link>" . $this->imageLink->text() . "</link>",
                $this->image->render()
            );
        }
        if (!$this->textInputLink->isEmpty() && !$this->textInputTitle->isEmpty()
            && !$this->textInputDescription->isEmpty() && !$this->textInputName->isEmpty()
        ) {
            $output .= str_replace(
                "<link>", "<link>" . $this->textInputLink->text() . "</link>",
                $this->textInput->render()
            );
        }
        if ($this->skipHours->xpath('hour')->length) {
            $output .= $this->skipHours->render();
        }
        if ($this->skipDays->xpath('day')->length) {
            $output .= $this->skipDays->render();
        }
        $cloud = $this->cloud();
        if (!empty($cloud['domain']) && !empty($cloud['path']) && !empty($cloud['protocol'])
            && !empty($cloud['port']) && !empty($cloud['registerProcedure'])
        ) {
            $output .= $this->cloud->render();
        }
        $output .= $this->body();

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        // ten element na wyjściu będzie zamieniony na $this->body()
        $replaceBody = new HtmlElement('replace_body');
        $replaceBody->insertTo($this->channel);

        $output = $this->prologRender();
        $output .= str_replace("<replace_body></replace_body>", $this->channelContent(), $this->root->render());

        // posprzątaj kod
        $replaceBody->destroy($replaceBody);

        return $output;
    }
}
