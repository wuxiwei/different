<?php

/**
 * Different.php
 * author:  吴希伟<wuxiwei@myhexin.com>
 * create:  2016/8/16
 * note:
 *    比较两个数组的差异，并高亮不同的地址。（尽量保证两个比较的数组键名对齐，最好不好出现同一级下分别是索引数组和关联数组。）
 */
class DifferentWithArray
{
    private $_diff1;
    private $_diff2;
    private $_add;      //蓝色
    private $_delete;   //红色
    private $_simple;   //黑色
    private $_diff;     //绿色
    private $_before = array();
    private $_end = array();

    public function __construct($diff1, $diff2, $simple = '#000', $diff = '#0f0', $add = '#00f', $delete = '#f00')
    {
        $this->_diff1 = $this->_isNotJson($diff1);
        $this->_diff2 = $this->_isNotJson($diff2);
        $this->_add = $add;
        $this->_delete = $delete;
        $this->_simple = $simple;
        $this->_diff = $diff;
        $this->dealDifferent();
    }

    /**
     * _isNotJson   判断是否是json
     * @param $str
     * @return mixed
     */
    private function _isNotJson($str)
    {
        if (is_array(json_decode($str, true))) {
            return json_decode($str, true);
        } else {
            return $str;
        }
    }

    /**
     * is_assoc 判断是否是索引数组
     * @param $arr
     * @return bool
     */
    private function _isAssoc($arr)
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * dealDifferent    调用入口
     */
    public function dealDifferent()
    {
        if ($this->_isAssoc($this->_diff1) && $this->_isAssoc($this->_diff2)) {
            //两个均为关联数组
            $this->_findDiffAssoc($this->_diff1, $this->_diff2, $this->_before, $this->_end);
        } else if (!$this->_isAssoc($this->_diff1) && !$this->_isAssoc($this->_diff2)) {
            //两个均为索引数组
            $this->_findDiffIndex($this->_diff1, $this->_diff2, $this->_before, $this->_end);
        }
    }

    /**
     * _findDiffIndex   处理索引数组
     * @param $temp1
     * @param $temp2
     * @param $before
     * @param $end
     * @param null $keys
     * @param null $ks
     */
    private function _findDiffIndex($temp1, $temp2, &$before, &$end, $keys = null, $ks = null)
    {
        foreach ($temp1 as $key => $value) {
            if (in_array($value, $temp2)) {
                $k = array_search($value, $temp2);
                if (is_array($value)) {

                    if ($this->_isAssoc($value) && $this->_isAssoc($temp2[$key])) {
                        $this->_findDiffAssoc($value, $temp2[$k], $b, $e, $key);
                        //把返回的数组添加到上一层
                        $this->_assignment($before, $key, $b[$key], null, $keys);
                        $this->_assignment($end, $k, $e[$k], null, $keys);
                    } else if (!$this->_isAssoc($value) && !$this->_isAssoc($temp2[$k])) {
                        $this->_findDiffIndex($value, $temp2[$k], $b, $e, $key, $k);
                        $this->_assignment($before, $key, $b, null, $keys);
                        $this->_assignment($end, $k, $e, null, $ks);
                    }
                } else {
                    $this->_assignment($before, $key, $value, $this->_simple, $keys);
                    $this->_assignment($end, $k, $value, $this->_simple, $ks);
                }
            } else {
                //value值temp1有temp2没有的情况
                if (is_array($value)) {
                    $this->_wholeArray("b", $b1, $key, $value, $keys);
                    //把返回的数组添加到这一层
                    $this->_assignment($before, $key, $b1[$keys], null, $keys);
                } else {
                    $this->_assignment($before, $key, $value, $this->_delete, $keys);
                }
            }
        }

        foreach ($temp2 as $key => $value) {
            if (!in_array($value, $temp1)) {
                if (is_array($value)) {
                    $this->_wholeArray("e", $e1, $key, $value, $ks);
                    //把返回的数组添加到这一层
                    $this->_assignment($end, $key, $e1[$ks], null, $ks);
                } else {
                    $this->_assignment($end, $key, $value, $this->_add, $ks);
                }
            }
        }
    }

    /**
     * _findDiffAssoc   处理关联数组（递归）
     * @param $temp1
     * @param $temp2
     * @param $before
     * @param $end
     * @param null $keys
     */
    private function _findDiffAssoc($temp1, $temp2, &$before, &$end, $keys = null)
    {
        foreach ($temp1 as $key => $value) {
            if (array_key_exists($key, $temp2)) {
                //key值temp1和temp2都存在情况
                if (is_array($value)) {
                    if ($this->_isAssoc($value) && $this->_isAssoc($temp2[$key])) {
                        $this->_findDiffAssoc($value, $temp2[$key], $b, $e, $key);
                        //把返回的数组添加到上一层
                        $this->_assignment($before, $key, $b[$key], null, $keys);
                        $this->_assignment($end, $key, $e[$key], null, $keys);
                    } else if (!$this->_isAssoc($value) && !$this->_isAssoc($temp2[$key])) {
                        $this->_findDiffIndex($value, $temp2[$key], $b, $e, $key, $key);
                        $this->_assignment($before, $key, $b[$key], null, $keys);
                        $this->_assignment($end, $key, $e[$key], null, $keys);
                    } else {
                        //如果相同的key值，数组分别为索引和关联。直接全部子元素都不同处理。
                        $this->_wholeArray("b", $b, $key, $value, $keys);
                        $this->_assignment($before, $key, $b, null, $keys);
                        $this->_wholeArray("e", $e, $key, $temp2[$key], $keys);
                        $this->_assignment($end, $key, $e, null, $keys);
                    }
                } else {
                    if ($value == $temp2[$key]) {
                        $this->_assignment($before, $key, $value, $this->_simple, $keys);
                        $this->_assignment($end, $key, $value, $this->_simple, $keys);

                    } else {
                        $this->_assignment($before, $key, $value, $this->_diff, $keys);
                        $this->_assignment($end, $key, $temp2[$key], $this->_diff, $keys);
                    }
                }
            } else {
                //key值temp1有temp2没有的情况
                if (is_array($value)) {
                    //	echo $keys;
                    $this->_wholeArray("b", $b1, $key, $value, $keys);
                    //把返回的数组添加到这一层
                    //print_r($b);

                    $this->_assignment($before, $key, $b1, null, $keys);
                } else {
                    $this->_assignment($before, $key, $value, $this->_delete, $keys);
                }
            }
        }

        foreach ($temp2 as $key => $value) {
            //只需要处理key值temp2有temp1没有的情况
            if (!array_key_exists($key, $temp1)) {
                if (is_array($value)) {
                    $this->_wholeArray("e", $e1, $key, $value, $keys);
                    //把返回的数组添加到这一层
                    //print_r($e);echo "<br />";
                    $this->_assignment($end, $key, $e1, null, $keys);
                } else {
                    $this->_assignment($end, $key, $value, $this->_add, $keys);
                }
            }
        }

    }

    /**
     * _assignment  给数组赋值
     * @param $arr
     * @param $key
     * @param $value
     * @param null $color
     * @param null $keys
     */
    private function _assignment(&$arr, $key, $value, $color = null, $keys = null)
    {
        if ($color) {
            //赋单个值
            if ($keys) {
                $arr[$keys][$key] = "<b style='color:{$color};'>{$value}</b>";
            } else {
                $arr[$key] = "<b style='color:{$color};'>{$value}</b>";
            }
        } else {
            //赋数组值
            if ($keys) {
                $arr[$keys][$key] = $value;
            } else {
                $arr[$key] = $value;
            }
        }
    }

    /**
     * _wholeArray  如果整个数组都删除或添加了，直接全部标记为删除或添加，注意递归
     * @param $str
     * @param $reArr
     * @param $key
     * @param $array
     * @param null $keys
     */
    private function _wholeArray($str, &$reArr, $key, $array, $keys = null)
    {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $this->_wholeArray($str, $re, $k, $v, $key);
                //把返回的数组添加到这一层
                $this->_assignment($reArr, $k, $re[$key], null, $keys);
            } else {
                if ($str == "e") {
                    $this->_assignment($reArr, $k, $v, $this->_add, $keys);
                } else {
                    $this->_assignment($reArr, $k, $v, $this->_delete, $keys);
                }
            }
        }
    }

    public function getBefore()
    {
        return $this->_before;
    }

    public function getEnd()
    {
        return $this->_end;
    }
}
