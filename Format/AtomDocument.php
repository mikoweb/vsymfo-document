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
use vSymfo\Component\Document\Element\HtmlElement;

/**
 * Dokument Atom
 * 
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Component
 * @subpackage Document_Type
 */
class AtomDocument extends XmlDocument
{
    /**
     * @var HtmlElement
     */
    private $title = null;

    /**
     * @var HtmlElement
     */
    private $updated = null;

    /**
     * @var HtmlElement
     */
    private $id = null;

    /**
     * @var OptionsResolver
     */
    private $authorResolver = null;

    /**
     * @var HtmlElement
     */
    private $author = null;

    /**
     * @var HtmlElement
     */
    private $authorName = null;

    /**
     * @var HtmlElement
     */
    private $authorEmail = null;

    /**
     * @var HtmlElement
     */
    private $authorUri = null;

    /**
     * @var HtmlElement
     */
    private $linkSelf = null;

    /**
     * @var HtmlElement
     */
    private $subtitle = null;

    /**
     * @var HtmlElement
     */
    private $generator = null;

    /**
     * @var HtmlElement
     */
    private $icon = null;

    /**
     * @var HtmlElement
     */
    private $logo = null;

    /**
     * @var HtmlElement
     */
    private $rights = null;

    /**
     * @var OptionsResolver
     */
    private $categoryResolver = null;

    /**
     * @var array
     */
    private $category = array();

    /**
     * @var array
     */
    private $contributor = array();

    public function __construct()
    {
        parent::__construct();
        // zmiana elementu root
        $this->root->destroy($this->root);
        $this->root = new HtmlElement('feed');
        $this->root->attr('xmlns', 'http://www.w3.org/2005/Atom');

        // elementy obowiązkowe zawarte w feed
        $this->title = new HtmlElement('title');
        $this->title->insertTo($this->root);
        $this->updated = new HtmlElement('updated');
        $this->updated->insertTo($this->root);
        $this->id = new HtmlElement('id');
        $this->id->insertTo($this->root);

        // elementy opcjonalne zawarte w feed
        $this->author = new HtmlElement('author');
        $this->authorName = new HtmlElement('name');
        $this->authorName->insertTo($this->author);
        $this->authorEmail = new HtmlElement('email');
        $this->authorUri = new HtmlElement('uri');
        $this->linkSelf = new HtmlElement('link');
        $this->linkSelf->attr('rel', 'self');
        $this->subtitle = new HtmlElement('subtitle');
        $this->generator = new HtmlElement('generator');
        $this->icon = new HtmlElement('icon');
        $this->logo = new HtmlElement('logo');
        $this->rights = new HtmlElement('rights');

        // program generujący
        $this->generator('vSymfo Document Component');

        // konfiguracja niektórych elementów
        $this->setAuthorResolver();
        $this->setCategoryResolver();
    }

    /**
     * {@inheritdoc }
     */
    public function formatName()
    {
        return "atom";
    }

    /**
     * {@inheritdoc}
     */
    public function renameRoot($name)
    {
        throw new \RuntimeException('Operation not allowed. You can not change Atom root element.');
    }

    /**
     * Filtr tekstowy.
     * Wszystkie znaki w jednej linii + htmlspecialchars().
     * 
     * @param HtmlElement $htmlElement
     * @param string $text
     * @param string $attr
     */
    private function filterText(HtmlElement $htmlElement, $text, $attr = null)
    {
        if (is_string($attr)) {
            $htmlElement->attr($attr,
                htmlspecialchars(
                    S::create($text)->collapseWhitespace()
                    , ENT_QUOTES
                    , $this->encoding->render()
                )
            );
        } else {
            $htmlElement->text(
                htmlspecialchars(
                    S::create($text)->collapseWhitespace()
                    , ENT_QUOTES
                    , $this->encoding->render()
                )
            );
        }
    }

    /**
     * Filtr na adres URL.
     * 
     * @param HtmlElement $htmlElement
     * @param string $text
     * @param string $attr
     */
    private function filterLink(HtmlElement $htmlElement, $text, $attr = null)
    {
        if (is_string($attr)) {
            $htmlElement->attr($attr,
                filter_var($text, FILTER_VALIDATE_URL)
                    ? $text
                    : ''
            );
        } else {
            $htmlElement->text(
                filter_var($text, FILTER_VALIDATE_URL)
                    ? $text
                    : ''
            );
        }
    }

    /**
     * Filtr na adres email.
     * @param HtmlElement $htmlElement
     * @param string $text
     * @param string $attr
     */
    private function filterEmail(HtmlElement $htmlElement, $text, $attr = null)
    {
        if (is_string($attr)) {
            $htmlElement->attr($attr,
                filter_var($text, FILTER_VALIDATE_EMAIL)
                    ? $text
                    : ''
            );
        } else {
            $htmlElement->text(
                filter_var($text, FILTER_VALIDATE_EMAIL)
                    ? $text
                    : ''
            );
        }
    }

    /**
     * Filtr na prawidłową datę.
     * 
     * @param HtmlElement $htmlElement
     * @param string $text
     * 
     * @throws \UnexpectedValueException
     */
    private function filterDate(HtmlElement $htmlElement, $text)
    {
        $date = \DateTime::createFromFormat(\DateTime::ATOM, $text);
        if ($date === false) {
            throw new \UnexpectedValueException("Invalid ATOM date format. Must be: " . \DateTime::ATOM);
        }

        $htmlElement->text($date->format(\DateTime::ATOM));
    }

    /**
     * Tworzenie obiektu do weryfikacji tablicy
     * z danymi wejściowymi elementu 'author'
     */
    private function setAuthorResolver()
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired(array('name'));

        $resolver->setDefaults(array(
            'email' => '',
            'uri' => ''
        ));

        $resolver->setAllowedTypes('name', 'string');
        $resolver->setAllowedTypes('email', 'string');
        $resolver->setAllowedTypes('uri', 'string');

        $this->authorResolver = $resolver;
    }

    /**
     * Tworzenie obiektu do weryfikacji tablicy
     * z danymi wejściowymi elementu 'category'
     */
    private function setCategoryResolver()
    {
        $resolver = new OptionsResolver();
        $resolver->setRequired(array('term'));

        $resolver->setDefaults(array(
            'scheme' => '',
            'label' => ''
        ));

        $resolver->setAllowedTypes('term', 'string');
        $resolver->setAllowedTypes('scheme', 'string');
        $resolver->setAllowedTypes('label', 'string');

        $this->categoryResolver = $resolver;
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
     * Data - kiedy ten plik został wygenerowany.
     * Format: rok-miesiąc-dzieńTgodzina:minuty:sekundyStrefaCzasowa.
     * 
     * @param string $set
     * 
     * @return string
     * 
     * @throws \UnexpectedValueException
     */
    public function updated($set = null)
    {
        if (is_string($set)) {
            $this->lastModified($set);
        }

        return $this->updated->text();
    }

    /**
     * {@inheritdoc}
     */
    public function lastModified($set = null)
    {
        $lastModified = parent::lastModified($set);
        if (is_string($set) && $lastModified instanceof \DateTime) {
            $this->filterDate($this->updated, $lastModified->format(\DateTime::ATOM));
        }

        return $lastModified;
    }

    /**
     * Unikalny, niezmienny identyfikator: <id>
     * Każdy kanał musi mieć identyfikator w formacie URI,
     * który nigdy się nie zmieni i będzie wykorzystywany tylko do tego jednego kanału.
     * 
     * @param string $set
     * 
     * @return string
     */
    public function id($set = null)
    {
        if (is_string($set)) {
            $this->filterLink($this->id, $set);
        }

        return $this->id->text();
    }

    /**
     * {@inheritdoc}
     * @return array
     */
    public function author($set = null)
    {
        $encoding = $this->encoding->render();
        // filtr dla pól tekstowych
        $textFilter = function ($text) use($encoding) {
            return htmlspecialchars($text, ENT_QUOTES, $encoding);
        };

        // odłącz lub załącz znacznik 'author'
        $autorAttachDetach = function (HtmlElement $author, HtmlElement $authorName, HtmlElement $root) {
            if (!$authorName->isEmpty()) {
                $author->insertTo($root);
            } else {
                $author->detach();
            }
        };

        if (is_array($set)) {
            $options = $this->authorResolver->resolve($set);
            $author = parent::author($options['name']);
            $this->authorName->text($textFilter($author));
            $autorAttachDetach($this->author, $this->authorName, $this->root);

            if (!empty($options['email'])) {
                $this->filterEmail($this->authorEmail, $options['email']);
                $this->authorEmail->insertTo($this->author);
            } else {
                $this->authorEmail->detach();
            }

            if (!empty($options['uri'])) {
                $authorUrl = parent::authorUrl($options['uri']);
                $this->authorUri->text($authorUrl);
                $this->authorUri->insertTo($this->author);
            } else {
                $this->authorUri->detach();
            }
        } elseif (is_string($set)) {
            $author = parent::author($set);
            $this->authorName->text($textFilter($author));
            $autorAttachDetach($this->author, $this->authorName, $this->root);
        }

        return array(
            'name' => $this->authorName->text(),
            'email' => $this->authorEmail->text(),
            'uri' => $this->authorUri->text()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function authorUrl($set = null)
    {
        $authorUrl = parent::authorUrl($set);
        if (is_string($set)) {
            $this->author(array(
                    'name' => $this->authorName->text(),
                    'email' => $this->authorEmail->text(),
                    'uri' => $authorUrl
                ));
        }

        return $authorUrl;
    }

    /**
     * Link do samego siebie
     * 
     * @param string $set
     * @return string
     */
    public function linkSelf($set = null)
    {
        if (is_string($set)) {
            $this->filterLink($this->linkSelf, $set, 'href');
            $val = $this->linkSelf->attr('href');
            if (!empty($val)) {
                $this->linkSelf->insertTo($this->root);
            } else {
                $this->linkSelf->detach();
            }
        }

        return $this->linkSelf->attr('href');
    }

    /**
     * Podtytuł
     * 
     * @param string $set
     * 
     * @return string
     */
    public function subtitle($set = null)
    {
        if (is_string($set)) {
            $this->filterText($this->subtitle, $set);
            if (!$this->subtitle->isEmpty()) {
                $this->subtitle->insertTo($this->root);
            } else {
                $this->subtitle->detach();
            }
        }

        return $this->subtitle->text();
    }

    /**
     * Generator (program generujący) kanału
     * 
     * @param string $set
     * 
     * @return string
     */
    public function generator($set = null)
    {
        if (is_string($set)) {
            $this->filterText($this->generator, $set);
            if (!$this->generator->isEmpty()) {
                $this->generator->insertTo($this->root);
            } else {
                $this->generator->detach();
            }
        }

        return $this->generator->text();
    }

    /**
     * Zawartość tego elementu jest ścieżką do ikony kanału.
     * Ikona ma być kwadratowa i musi dobrze wyglądać przeskalowana do małych rozmiarów.
     * 
     * @param string $set
     * 
     * @return string
     */
    public function icon($set = null)
    {
        if (is_string($set)) {
            $this->filterText($this->icon, $set);
            if (!$this->icon->isEmpty()) {
                $this->icon->insertTo($this->root);
            } else {
                $this->icon->detach();
            }
        }

        return $this->icon->text();
    }

    /**
     * Zawartość tego elementu jest ścieżką do loga kanału.
     * Logo będzie wyświetlane większe, niż ikona i ma być dwa razy szersze, niż wyższe.
     * 
     * @param string $set
     * 
     * @return string
     */
    public function logo($set = null)
    {
        if (is_string($set)) {
            $this->filterText($this->logo, $set);
            if (!$this->logo->isEmpty()) {
                $this->logo->insertTo($this->root);
            } else {
                $this->logo->detach();
            }
        }

        return $this->logo->text();
    }

    /**
     * Informacje o prawie autorskim dotyczącym treści całego kanału lub jego wpisów.
     * 
     * @param string $set
     * 
     * @return string
     */
    public function rights($set = null)
    {
        if (is_string($set)) {
            $this->filterText($this->rights, $set);
            if (!$this->rights->isEmpty()) {
                $this->rights->insertTo($this->root);
            } else {
                $this->rights->detach();
            }
        }

        return $this->rights->text();
    }

    /**
     * Znajdź id kategorii o podanym atrybucie term.
     * 
     * @param $term
     * 
     * @return null|integer
     */
    private function getCategoryIdByTerm($term)
    {
        foreach ($this->category as $id=>$category) {
            if ($category->attr('term') == $term) {
                return $id;
            }
        }

        return null;
    }

    /**
     * Dodaj kategorię kanału
     * 
     * @param array $values
     */
    public function addCategory(array $values)
    {
        $options = $this->categoryResolver->resolve($values);
        $term = $options['term'];
        $id = $this->getCategoryIdByTerm($term);

        if (is_int($id) && isset($this->category[$id])) {
            $category = $this->category[$id];
        } else {
            $category = new HtmlElement('category');
            $this->category[] = $category;
            $this->filterText($category, $term, 'term');
            $category->insertTo($this->root);
        }

        if (!empty($options['scheme'])) {
            $this->filterLink($category, $options['scheme'], 'scheme');
        } else {
            $category->removeAttr('scheme');
        }

        if (!empty($options['label'])) {
            $this->filterText($category, $options['label'], 'label');
        } else {
            $category->removeAttr('label');
        }
    }

    /**
     * Usuń kategorię o atrybucie term="{$term}"
     * 
     * @param string $term
     * 
     * @throws \InvalidArgumentException
     */
    public function removeCategory($term)
    {
        if (!is_string($term)) {
            throw new \InvalidArgumentException('Term is not string.');
        }

        $id = $this->getCategoryIdByTerm($term);
        if (is_int($id) && isset($this->category[$id])) {
            $this->category[$id]->destroy($this->category[$id]);
            unset($this->category[$id]);
        }
    }

    /**
     * Znajdź id współtwórcy/pomocnika o podanej nazwie.
     * 
     * @param $name
     * 
     * @return null|integer
     */
    private function getContributorIdByName($name)
    {
        foreach ($this->contributor as $id=>$contributor) {
            if ($contributor['name']->text() == $name) {
                return $id;
            }
        }

        return null;
    }

    /**
     * Dodaj współtwórcę/pomocnika.
     * Zawiera takie same elementy jak <author>, ale oznacza, że opisywana
     * osoba nie jest autorem, ale miała swój wkład w powstanie tego, co przedstawia kanał/wpis.
     * 
     * @param array $values
     */
    public function addContributor(array $values)
    {
        $options = $this->authorResolver->resolve($values);
        $name = $options['name'];
        $id = $this->getContributorIdByName($name);

        if (is_int($id) && isset($this->contributor[$id])) {
            $contributor = $this->contributor[$id];
        } else {
            $contributor = array(
                'contributor' => new HtmlElement('contributor'),
                'name' => new HtmlElement('name'),
                'email' => new HtmlElement('email'),
                'uri' => new HtmlElement('uri')
            );
            $this->contributor[] = $contributor;
            $this->filterText($contributor['name'], $name);
            $contributor['name']->insertTo($contributor['contributor']);
            $contributor['contributor']->insertTo($this->root);
        }

        if (!empty($options['email'])) {
            $this->filterEmail($contributor['email'], $options['email']);
            $contributor['email']->insertTo($contributor['contributor']);
        } else {
            $contributor['email']->detach();
        }

        if (!empty($options['uri'])) {
            $this->filterLink($contributor['uri'], $options['uri']);
            $contributor['uri']->insertTo($contributor['contributor']);
        } else {
            $contributor['uri']->detach();
        }
    }

    /**
     * Usuń współtwórcę/pomocnika o podanej nazwie.
     * 
     * @param string $name
     * 
     * @throws \InvalidArgumentException
     */
    public function removeContributor($name)
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException('Name is not string.');
        }

        $id = $this->getContributorIdByName($name);
        if (is_int($id) && isset($this->contributor[$id])) {
            $contributor = $this->contributor[$id];
            $contributor["name"]->destroy($contributor["name"]);
            $contributor["email"]->destroy($contributor["email"]);
            $contributor["uri"]->destroy($contributor["uri"]);
            $contributor["contributor"]->destroy($contributor["contributor"]);
            unset($this->contributor[$id]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        // ten element na wyjściu będzie zamieniony na $this->body()
        $replaceBody = new HtmlElement('replace_body');
        $replaceBody->text('replace');
        $replaceBody->insertTo($this->root);

        $output = $this->prologRender();
        $output .= str_replace("<replace_body>replace</replace_body>", $this->body(), $this->root->render(false, true));

        // posprzątaj kod
        $replaceBody->destroy($replaceBody);

        return $output;
    }
}
