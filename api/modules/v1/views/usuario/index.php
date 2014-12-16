<div class="usuarios-form">

                <form method="post" action="crear" role="form">

                <div class="form-group col-md-12">
                    <label for="nombre" class="col-md-2 control-label">Nombre:</label>
                    <div class="col-md-10">
                        <input type="text" class="form-control" name="nombre" placeholder="Nombre" required>
                    </div>
                </div>

                <div class="form-group col-md-12">
                    <label for="nombre" class="col-md-2 control-label">Email:</label>
                    <div class="col-md-10">
                        <input type="text" class="form-control" name="correo" placeholder="Email" required>
                    </div>
                </div>

                <div class="form-group col-md-12">
                    <label for="nombre" class="col-md-2 control-label">Contraseña:</label>
                    <div class="col-md-10">
                        <input type="password" class="form-control" name="contrasena" placeholder="Contraseña">
                    </div>
                </div>

                <div class="form-group col-md-12">
                    <label for="nombre" class="col-md-2 control-label">Sexo:</label>
                    <div class="col-md-10">
                        <select id="sexo" name="sexo" class="form-control" required>
                            <option value="">Selecciona el sexo</option>
                            <option value="m">Masculino</option>
                            <option value="f">Femenino</option>
                        </select>
                    </div>
                </div>

                <div class="form-group col-md-12 field-usuarios-estado">
                    <label class="col-md-2 control-label">Estado del usuario:</label>
                    <div class="col-md-10">
                        <select class="form-control" name="estado" required id="usuarios-estado">
                            <option value="1">Disponible</option>
                            
                        </select>
                    </div>
                </div>

                <div class="form-group col-md-12">
                    <label for="nombre" class="col-md-2 control-label">Perfil:</label>
                    <div class="col-md-10">
                        <select id="perfil" name="perfil" class="form-control">
                            <option value="">Selecciona un perfil</option>
                                <option value="Jugador">Jugador</option>
                        </select>
                    </div>
                </div>

                <div class="form-group col-md-12">
                    <label for="nombre" class="col-md-2 control-label">Telefono:</label>
                    <div class="col-md-10">
                        <input type="text" class="form-control" name="telefono" placeholder="Telefono" required>
                    </div>
                </div>


                <div class= "col-md-12">
                    <div class="form-group col-md-6 text-center">
                        <button class=" btn btn-primary" type="submit">Crear</button>
                    </div>
                </div>

                </form>

            </div>
