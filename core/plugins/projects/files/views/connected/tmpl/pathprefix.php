<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2018 HUBzero Foundation, LLC.
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
 * @author    Zach Weidner <zweidner@purdue.edu>
 * @copyright Copyright 2005-2018 HUBzero Foundation, LLC.
 * @license   http://opensource.org/licenses/MIT MIT
 */

// No direct access
defined('_HZEXEC_') or die();

use Hubzero\Filesystem\Entity;

$this->css()
     ->js();

$subdirlink = $this->subdir ? '&subdir=' . urlencode($this->subdir) : '';
$parentUrl = Route::url($this->model->link('files') . '&action=browse&connection=' . $this->connection->id . '&subdir=' . $this->parent);
?>

<div id="abox-content">
	<h3>
		<?php echo Lang::txt('PLG_PROJECTS_FILES_CONNECTED_PREFIX_NOT_SET'); ?>
	</h3>

	<?php if ($this->getError()) : ?>
		<?php echo '<p class="witherror">' . $this->getError() . '</p>'; ?>
	<?php else : ?>
		<form id="hubForm-ajax" method="post" action="<?php echo Route::url($this->url . '&'); ?>">
			<fieldset >
				<input type="hidden" name="id" value="<?php echo $this->model->get('id'); ?>" />
				<input type="hidden" name="action" value="setprefix" />
				<input type="hidden" name="task" value="view" />
				<input type="hidden" name="active" value="files" />
				<input type="hidden" name="subdir" value="<?php echo $this->subdir; ?>" />
				<input type="hidden" name="option" value="<?php echo $this->option; ?>" />

				<div id="dirs" class="dirs">
					<h4>
						<?php echo Lang::txt('PLG_PROJECTS_FILES_CONNECTED_SELECT_PREFIX'); ?>
					</h4>
				</div>

				<ul class="sample">
					<?php if ($this->subdir != ''): ?>
						<li>
							<a href="<?php echo $parentUrl; ?>" class="uptoparent"><?php echo Lang::txt('PLG_PROJECTS_FILES_BACK_TO_PARENT_DIR'); ?></a>
						</li>
					<?php endif; ?>
						<li>
							<?php
							echo '<input type="radio" name="prefix" value="' . urlencode($this->current_dir->getPath()) . '" />';
							echo \Components\Projects\Models\File::drawIcon($this->current_dir->getExtension());
							?>
							<span><?php echo Lang::txt('PLG_PROJECTS_FILES_CONNECTED_CURRENT_DIRECTORY', $this->current_dir->getDisplayName()); ?></span>
						</li>
					<?php
					foreach ($this->items as $dir) : ?>
						<li>
							<?php 
							echo '<input type="radio" name="prefix" value="' . urlencode($dir->getPath()) . '" />';
							echo \Components\Projects\Models\File::drawIcon($dir->getExtension());
							echo '<a href="'. Route::url($this->model->link('files') . '&action=browse&connection=' . $this->connection->id . '&subdir=' . $dir->getPath()) . '">' . $dir->getDisplayName() . '</a>'; ?>
						</li>
					<?php endforeach; ?>
				</ul>

				<p class="submitarea">
					<input type="submit" class="btn" value="<?php echo Lang::txt('PLG_PROJECTS_FILES_CONNECTED_SELECT'); ?>" />
				</p>
			</fieldset>
		</form>
	<?php endif; ?>
</div>
