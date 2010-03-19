<?php
/**
 * Atomik Framework
 * Copyright (c) 2008-2009 Maxime Bouroumeau-Fuseau
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package Atomik
 * @subpackage Db
 * @author Maxime Bouroumeau-Fuseau
 * @copyright 2008-2009 (c) Maxime Bouroumeau-Fuseau
 * @license http://www.opensource.org/licenses/mit-license.php
 * @link http://www.atomikframework.com
 */

/** Atomik_Db_Script_Interface */
require_once 'Atomik/Db/Script/Interface.php';

/** Atomik_Model_Descriptory */
require_once 'Atomik/Model/Descriptor.php';

/** Atomik_Model_Export */
require_once 'Atomik/Model/Export.php';

/**
 * @package Atomik
 * @subpackage Db
 */
class Atomik_Db_Script_Model implements Atomik_Db_Script_Interface
{
	/**
	 * @var Atomik_Model_Descriptor
	 */
	protected $_descriptor;
	
	/**
	 * Returns an array of script obtained from a directory
	 * 
	 * @param	string	$dir
	 * @param 	string	$parent
	 * @return 	array
	 */
	public static function getScriptFromDir($dir, $parent = '')
	{
		$scripts = array();
		
		foreach (new DirectoryIterator($dir) as $file) {
			if ($file->isDot() || substr($file->getFilename(), 0, 1) == '.') {
				continue;
			}
			
			$filename = $file->getFilename();
			if (strpos($filename, '.') !== false) {
				$filename = substr($filename, 0, strrpos($filename, '.'));
			}
			$className = trim($parent . '_' . $filename, '_');
			
			if ($file->isDir()) {
				$scripts = array_merge($scripts, self::getScriptFromDir($file->getPathname(), $className));
				continue;
			}
			
			require_once $file->getPathname();
			if (!class_exists($className, false)) {
				continue;
			}
			
			$descriptor = Atomik_Model_Descriptor::factory($className);
			$scripts[] = new Atomik_Db_Script_Model($descriptor);
		}
		
		return $scripts;
	}
	
	/**
	 * Constructor
	 * 
	 * @param	Atomik_Model_Descriptor	$descriptor
	 */
	public function __construct(Atomik_Model_Descriptor $descriptor = null)
	{
		$this->setModelDescriptor($descriptor);
	}
	
	/**
	 * Sets the model descriptor
	 * 
	 * @param	Atomik_Model_Descriptor	$descriptor
	 */
	public function setModelDescriptor(Atomik_Model_Descriptor $descriptor)
	{
		$this->_descriptor = $descriptor;
	}
	
	/**
	 * Returns the model descriptor
	 * 
	 * @return	Atomik_Model_Descriptor
	 */
	public function getModelDescriptor()
	{
		return $this->_descriptor;
	}
	
	/**
	 * Returns the sql needed to create the table associated to the model
	 */
	public function getSql()
	{
		$exporter = new Atomik_Model_Export();
		return $exporter->export($this->_descriptor);
	}
	
	/**
	 * @see Atomik_Db_Script_Model::getSql()
	 */
	public function __toString()
	{
		return $this->getSql();
	}
}