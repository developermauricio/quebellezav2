<textarea  type="text" <?php 
    if ($this->get('readonly')) {
        echo 'readonly="true"';
    }
 ?> name="<?php echo $this->getName(); ?>" class="form-control <?php echo $this->getCssClassName(); ?>"><?php echo $this->value; ?></textarea>