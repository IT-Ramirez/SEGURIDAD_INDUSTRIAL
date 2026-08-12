<?php
	// Establecer conexión con la base de datos
	require_once __DIR__ . '/dbconnection.php';
	

	// Función para obtener el número de filas de una consulta
	function getNumRowsQuery($query) {
		global $sqlconnection;
		if ($result = $sqlconnection->query($query))
			return $result->num_rows;  // Devuelve el número de filas resultantes
		else
			echo "Error en la consulta!";
	}

	// Función para ejecutar una consulta y mostrar los resultados
	function getFetchAssocQuery($query) {
		global $sqlconnection;
		if ($result = $sqlconnection->query($query)) {
			
			// Recorrer cada fila del resultado y mostrar ciertos campos
			while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
        		echo "\n", $row["itemID"], $row["menuID"], $row["menuItemName"], $row["price"];
    		}

    		// Devuelve el resultado completo
			return ($result);
		}
		else
			echo "Error en la consulta!";
			echo $sqlconnection->error;  // Muestra el error en la conexión
	}

	// Función para obtener el último ID insertado en una tabla
	function getLastID($id,$table) {
		global $sqlconnection;

		// Consulta para obtener el valor máximo del ID en una tabla
		$query = "SELECT MAX({$id}) AS {$id} from {$table} ";

		if ($result = $sqlconnection->query($query)) {
			
			$res = $result->fetch_array();

			// Si no hay ningún ID en la tabla, devuelve 0
			if ($res[$id] == NULL)
				return 0;

			// Devuelve el último ID
			return $res[$id];
		}
		else {
			echo $sqlconnection->error;  // Muestra el error
			return null;
		}
	}

	// Función para contar cuántos registros tienen un ID específico en una tabla
	function getCountID($idnum,$id,$table) {
		global $sqlconnection;

		// Consulta SQL para contar los registros que coinciden con el ID
		$query = "SELECT COUNT({$id}) AS {$id} from {$table} WHERE {$id}={$idnum}";

		if ($result = $sqlconnection->query($query)) {
			
			$res = $result->fetch_array();

			// Si no hay coincidencias, devuelve 0
			if ($res[$id] == NULL)
				return 0;

			// Devuelve el número de coincidencias
			return $res[$id];
		}
		else {
			echo $sqlconnection->error;  // Muestra el error
			return null;
		}
	}

	// Función para obtener el total de ventas de una orden específica
	function getSalesTotal($orderID) {
		global $sqlconnection;
		$total = null;

		// Consulta para obtener el total de una orden específica
		$query = "SELECT total FROM tbl_order WHERE orderID = ".$orderID;

		if ($result = $sqlconnection->query($query)) {
		
			// Si encuentra un resultado, lo devuelve
			if ($res = $result->fetch_array()) {
				$total = $res[0];
				return $total;
			}

			// Devuelve el total o null si no se encuentra
			return $total;
		}

		else {
			echo $sqlconnection->error;  // Muestra el error
			return null;
		}
	}

	// Función para obtener el total acumulado de ventas en un periodo específico
	function getSalesGrandTotal($duration) {
		global $sqlconnection;
		$total = 0;

		// Si la duración es "ALLTIME", obtiene el total de ventas acumulado
		if ($duration == "ALLTIME") {
			$query = "
					SELECT SUM(total) as grandtotal
					FROM tbl_order
					";
		}

		// Si la duración es día, semana o mes, ajusta la consulta
		else if ($duration == ("DAY" || "MONTH" || "WEEK")) {
			$query = "
					SELECT SUM(total) as grandtotal
					FROM tbl_order
					WHERE order_date > DATE_SUB(NOW(), INTERVAL 1 ".$duration.")
					";
		}

		// Si la duración no es válida, devuelve null
		else 
			return null;

		// Ejecuta la consulta y devuelve el total acumulado
		if ($result = $sqlconnection->query($query)) {
			while ($res = $result->fetch_array(MYSQLI_ASSOC)) {
				$total += $res['grandtotal'];
			}
			return $total;
		}
		else {
			echo $sqlconnection->error;  // Muestra el error
			return null;
		}
	}

	// Función para actualizar el total de una orden
	function updateTotal($orderID) {
    global $sqlconnection;

    // Sumamos únicamente las cantidades de los artículos pedidos en la orden
    $query = "
        UPDATE tbl_order o
        INNER JOIN (
            SELECT SUM(OD.quantity) AS total_articulos
            FROM tbl_orderdetail OD
            WHERE OD.orderID = ?
        ) x ON 1 = 1
        SET o.total = IFNULL(x.total_articulos, 0)
        WHERE o.orderID = ?
    ";

    if ($stmt = $sqlconnection->prepare($query)) {
        // Pasamos el orderID dos veces para los dos marcadores '?'
        $stmt->bind_param("ii", $orderID, $orderID);
        
        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            error_log("Error al ejecutar el total de la orden: " . $stmt->error);
            return false;
        }
    } else {
        error_log("Error al preparar el total de la orden: " . $sqlconnection->error);
        return false;
    }
}


	// Función para agregar un nuevo empleado
	function addstaff($username, $pwd, $role){
		global $sqlconnection;

		// Consulta SQL para insertar un nuevo empleado en la tabla tbl_staff
		$query = "INSERT INTO `tbl_staff` (`staffID`, `username`, `password`, `status`, `role`) VALUES (NULL, '$username', '$pwd', 'Online', '$role');";
			
		// Ejecutar la consulta y mostrar mensaje de éxito o error
		if ($sqlconnection->query($query) === TRUE) {
				echo "Empleado agregado satisfactoriamente ";
			} 
		else {
				// Manejar error
				echo "Ha ocurrido un error cerca de la línea 89";
				echo $sqlconnection->error;
		}
	}

	// Función para actualizar el inventario
	function update_inventario($sqlconnection, $itemID, $quantity) { 
    if (empty($itemID) || empty($quantity) || $quantity <= 0) {
        return ['status' => 'error', 'message' => 'ID de artículo o cantidad no válidos.'];
    }

    $checkQuery = "SELECT cantidad_disponible FROM tbl_menuitem WHERE itemID = ?";
    $stmt = $sqlconnection->prepare($checkQuery);
    $stmt->bind_param("i", $itemID);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $cantidad_disponible = $row['cantidad_disponible'];

        if ($cantidad_disponible >= $quantity) {
            $updateQuery = "UPDATE tbl_menuitem SET cantidad_disponible = cantidad_disponible - ? WHERE itemID = ?";
            $updateStmt = $sqlconnection->prepare($updateQuery);
            $updateStmt->bind_param("ii", $quantity, $itemID);
            
            if ($updateStmt->execute()) {
                return ['status' => 'success', 'message' => 'Se ha actualizado el inventario correctamente.'];
            } else {
                return ['status' => 'error', 'message' => 'Ha ocurrido un error al actualizar el inventario.'];
            }
        } else {
            return ['status' => 'error', 'message' => 'Producto insuficiente.'];
        }
    } else {
        return ['status' => 'error', 'message' => 'No se encontró el artículo con el ID proporcionado.'];
    }
}
?>
