<?php
/**
 * Atomik Framework
 * Copyright (c) 2008 Maxime Bouroumeau-Fuseau
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
 * @subpackage Plugins
 * @author Maxime Bouroumeau-Fuseau
 * @copyright 2008 (c) Maxime Bouroumeau-Fuseau
 * @license http://www.opensource.org/licenses/mit-license.php
 * @link http://www.atomikframework.com
 */

/**
 * Backend plugin
 * 
 * @package Atomik
 * @subpackage Plugins
 */
class BackendPlugin
{
	/**
	 * Default configuration
	 * 
	 * @var array 
	 */
	public static $config = array(
		
		/* the trigger is the word used in the first segment of the 
		 * action to start the backend */
		'trigger' => 'admin',
	
		/* backend layout */
		'layout' => '_layout.phtml'
	
	);
	
	/**
	 * Plugin initialization
	 *
	 * @param array $config
	 * @return bool
	 */
	public static function start($config)
	{
        /* config */
        self::$config = array_merge(self::$config, $config);
        
        /* high priority */
		Atomik::listenEvent('Atomik::Dispatch::Before',
			array('BackendPlugin', 'onAtomikDispatchBefore'), 0, true);
			
		return false;
	}
	
	/**
	 * Checks if the backend trigger is present and if true, starts the backend
	 */
	public static function onAtomikDispatchBefore(&$cancel)
	{
		/* checks if the uri starts with the backend trigger */
		$trigger = self::$config['trigger'];
		$segments = explode('/', Atomik::get('request_uri'));
		$key = array_shift($segments);
		if ($key != $trigger) {
			return;
		}
		
		/** Atomik_Backend */
		require_once 'Atomik/Backend.php';
		
		/* extract the plugin name from segments */
		if (count($segments) > 0) {
			$plugin = array_shift($segments);
			if (count($segments) == 0) {
				$segments = array('index');
			}
		} else {
			$plugin = 'backend';
			$segments = array('dashboard');
		}
		
		$pluginDir = rtrim(Atomik::path(Atomik::get('atomik/dirs/plugins')), '/') . '/';
		$layoutDir = $pluginDir . 'Backend/share/views';
		$pluginDir .= ucfirst($plugin) . '/backend/';
		
		/* checks if the plugin supports the backend */
		if (!is_dir($pluginDir)) {
			Atomik_Session::flash('The plugin ' . $plugin . ' does have any backend features', 'error');
			Atomik_Backend::redirect('backend/dashboard');
		}
		
		/* modify current config */
		$uri = implode('/', $segments);
		Atomik::set(array(
			'request_uri' => $uri,
			'request' => Atomik::route($uri, $_GET),
			'backend' => array(
				'plugin' => $plugin,
				'title' => 'Atomik Backend'
			),
			'atomik' => array(
				'dirs' => array(
					'actions' =>  $pluginDir . 'actions',
					'views' =>  array(
						$layoutDir, 
						$pluginDir . 'views'
					)
				)
			)
		));
		
		/* backend layout */
		Atomik::loadPlugin('Layout');
		LayoutPlugin::$config['global'] = self::$config['layout'];
		
		/* default tabs */
		Atomik_Backend::addTab('Dashboard', 'Backend', 'dashboard');
		Atomik_Backend::addTab('Pages', 'Backend', 'pages');
		
		Atomik::fireEvent('Backend::Start');
		
		/* plugins can provide a bootstrap file */
		$bootstrap = $pluginDir . 'bootstrap.php';
		if (file_exists($bootstrap)) {
			include $bootstrap;
		}
	}
}