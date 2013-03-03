<?php require 'header.html.php' ?>

<div class="page-header">
	<h1><?php echo $this->get('pageTitle') ?></h1>
</div>

<?php if ( $this->get('error') ): ?>
<div class="alert alert-error">
	<a class="close">&times;</a>

	<?php echo $this->get('error'); ?>
</div>
<?php endif ?>

<div class="row">
	<div class="span12">
		<form method="post" action="<?php echo $this->app->getRootPath() ?>signin" class="form-signin form-horizontal well">
			<br>

			<fieldset>
				<div class="control-group">
					<label class="control-label" for="email">Email address</label>

					<div class="controls">
						<input id="email" name="email" class="input-xlarge" type="text" value="<?php echo $this->get('email') ?>">
					</div>
				</div>

				<div class="control-group">
					<label class="control-label" for="password">Password</label>

					<div class="controls">
						<input id="password" name="password" class="input-xlarge" type="password">
					</div>
				</div>

				<div class="control-group">
					<div class="controls">
						<button class="btn btn-inverse" type="submit"><i class="icon-hand-right icon-white"></i> Sign in</button>
					</div>
				</div>
			</fieldset>
		</form>
	</div>
</div>

<?php require 'footer.html.php' ?>