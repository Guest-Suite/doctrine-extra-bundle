<?php

namespace Alex\DoctrineExtraBundle\Graphviz;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;

use Alex\DoctrineExtraBundle\Graphviz\Pass\ImportMetadataPass;
use Alex\DoctrineExtraBundle\Graphviz\Pass\InheritancePass;
use Alex\DoctrineExtraBundle\Graphviz\Pass\ShortNamePass;

use Alom\Graphviz\Digraph;

class DoctrineMetadataGraph extends Digraph
{
    public function __construct(ObjectManager $manager, array $options)
    {
        parent::__construct('G');

        $nodeConfig = ['shape' => 'record'];

        if ($options['font']) {
            $this->attr('graph', [
                'fontname' => $options['font']
            ]);

            $nodeConfig['font'] = $options['font'];
        }

        $this->attr('node', $nodeConfig);
        $this->set('rankdir', 'LR');

        $data = $this->createData($manager, $options);

        $clusters = [];

        foreach ($data['entities'] as $class => $entity) {
            list($cluster, $label) = $this->splitClass($class);
            if (!isset($clusters[$cluster])) {
                $escaped = str_replace("\\", "_", $cluster);
                $clusters[$cluster] = $this->subgraph('cluster_'.$escaped)
                    ->set('label', $cluster)
                    ->set('style', 'filled')
                    ->set('color', '#eeeeee')
                    ->attr('node', array(
                        'style' => 'filled',
                        'color' => '#eecc88',
                        'fillcolor' => '#FCF0AD',
                    ))
                ;
            }
;
            $label = $this->getEntityLabel($label, $entity);
            $clusters[$cluster]->node($class, array(
                'label' => '"'.$label.'"',
                '_escaped' => false
            ));
        }

        foreach ($data['relations'] as $association) {
            if (true === $options['useRandomEdgeColor'] ?? false) {
                $color = $this->randomColor();
            } else {
                $color = '88888888';
            }

            $attr = array();
            switch ($association['type']) {
                case 'one_to_one':
                    $attr['color'] = '#'.$color;
                    $attr['dir'] = 'both';
                    $attr['arrowtail'] = 'dot';
                    $attr['arrowhead'] = 'dot';
                    break;
                case 'one_to_many':
                    $attr['color'] = '#'.$color;
                    $attr['dir'] = 'both';
                    $attr['arrowtail'] = 'dot';
                    $attr['arrowhead'] = 'crow';
                    break;
                case 'many_to_one':
                    $attr['color'] = '#'.$color;
                    $attr['dir'] = 'both';
                    $attr['arrowtail'] = 'crow';
                    $attr['arrowhead'] = 'dot';
                    break;
                case 'many_to_many':
                    $attr['color'] = '#'.$color;
                    $attr['dir'] = 'both';
                    $attr['arrowtail'] = 'crow';
                    $attr['arrowhead'] = 'crow';
                    break;
                case 'extends':
            }

            $this->edge(array($association['from'], $association['to']), $attr);
        }
    }

    private function randomColorPart() {
        return str_pad(dechex(mt_rand(0, 255)), 2, '0', STR_PAD_LEFT);
    }

    private function randomColor() {
        return $this->randomColorPart().$this->randomColorPart().$this->randomColorPart().'FF';
    }

    private function createData(ObjectManager $manager, array $options)
    {
        $data = array('entities' => array(), 'relations' => array());
        $passes = array(
            new ImportMetadataPass($options['includeReverseEdges'] ?? true),
            new InheritancePass(),
        );

        foreach ($passes as $pass) {
            $data = $pass->process($manager->getMetadataFactory(), $options, $data);
        }

        return $data;
    }

    private function getEntityLabel($class, $entity)
    {
        // Beware that this value will not be escaped, so every special character must be escaped

        $result = '{{<__class__> '.$class.'|';

        foreach ($entity['associations'] as $name => $val) {
            list($ignored, $val) = $this->splitClass($val);
            $escVal = str_replace("\\", "\\\\", $val);
            $result .= '<'.$name.'> '.$name.' : '.$escVal." \\l|";
        }

        foreach ($entity['fields'] as $name => $val) {
            $escVal = str_replace("\\", "\\\\", $val);
            $result .= $name.' : '.$escVal." \\l";
        }

        $result .= '}}';

        return $result;
    }

    private function splitClass($entityName)
    {
        $pos = strrpos($entityName, "\\");
        if ($pos === false) {
            return array('', $entityName);
        }

        return array(
            substr($entityName, 0, $pos),
            substr($entityName, $pos + 1)
        );
    }
}
