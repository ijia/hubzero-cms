<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2015 HUBzero Foundation, LLC.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * HUBzero is a registered trademark of Purdue University.
 *
 * @package   hubzero-cms
 * @copyright Copyright 2005-2015 HUBzero Foundation, LLC.
 * @license   http://opensource.org/licenses/MIT MIT
 */

// No direct access.
defined('_HZEXEC_') or die();

$data = '';
foreach ($this->sections as $section)
{
	if ($section['area'] == 'collect')
	{
		echo (isset($section['metadata'])) ? $section['metadata'] : '';
		continue;
	}
	$data .= (isset($section['metadata'])) ? $section['metadata'] : '';
}

if ($this->model->params->get('show_ranking', 0) || $this->model->params->get('show_audience') || $this->model->params->get('supportedtag') || $data)
{
	$database = App::get('db');
	?>
	<div class="metadata">
		<?php
		if ($this->model->params->get('show_ranking', 0))
		{
			if ($this->model->isTool())
			{
				$stats = new \Components\Resources\Helpers\Usage\Tools($database, $this->model->id, $this->model->type, $this->model->rating);
			}
			else
			{
				$stats = new \Components\Resources\Helpers\Usage\Andmore($database, $this->model->id, $this->model->type, $this->model->rating);
			}

			$rank = round($this->model->ranking, 1);

			$r = (10*$rank);
			?>
			<dl class="rankinfo">
				<dt class="ranking">
					<span class="rank"><span class="rank-<?php echo $r; ?>" style="width: <?php echo $r; ?>%;">This resource has a</span></span> <?php echo number_format($rank, 1); ?> Ranking
				</dt>
				<dd>
					<p>
						Ranking is calculated from a formula comprised of <a href="<?php echo Route::url('index.php?option=' . $this->option . '&id=' . $this->model->id . '&active=reviews'); ?>">user reviews</a> and usage statistics. <a href="about/ranking/">Learn more &rsaquo;</a>
					</p>
					<div>
						<?php echo $stats->display(); ?>
					</div>
				</dd>
			</dl>
			<?php
		}

		if ($this->model->params->get('show_audience'))
		{
			include_once Component::path($this->option) . DS . 'models' . DS . 'audience.php';
			include_once Component::path($this->option) . DS . 'models' . DS . 'audience' . DS . 'level.php';

			$audience = \Components\Resources\Models\Audience::all()
				->whereEquals('rid', $this->model->id)
				->row();

			$this->view('_audience', 'view')
			     ->set('audience', $audience)
			     ->set('showtips', 1)
			     ->set('numlevels', 4)
			     ->set('audiencelink', $this->model->params->get('audiencelink'))
			     ->display();
		}

		if ($this->model->params->get('supportedtag'))
		{
			$rt = new \Components\Resources\Helpers\Tags($this->model->id);
			if ($rt->checkTagUsage($this->model->params->get('supportedtag'), $this->model->id))
			{
				include_once Component::path('com_tags') . DS . 'models' . DS . 'cloud.php';

				$tag = \Components\Tags\Models\Tag::oneByTag($this->model->params->get('supportedtag'));
			?>
			<p class="supported">
				<a href="<?php echo $this->model->params->get('supportedlink', Route::url('index.php?option=com_tags&tag=' . $tag->get('tag'))); ?>"><?php echo $this->escape(stripslashes($tag->get('raw_tag'))); ?></a>
			</p>
			<?php
			}
		}

		echo $data;
		?>
		<div class="clear"></div>
	</div><!-- / .metadata -->
	<?php
}
