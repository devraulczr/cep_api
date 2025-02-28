<?php 
$sql = [
    "host"      => "127.0.0.1",
    "username"  => "root",
    "password"  => "",
    "db"        => "cep_api"
];

$mysqli = new mysqli($sql["host"], $sql["username"], $sql["password"], $sql["db"]);
if ($mysqli->connect_error) {
    die("Falha na conexão: " . $mysqli->connect_error);
}
$input = file_get_contents("php://input");
$data = json_decode($input, true);

function validateApiKey($apiKey, $mysqli) {
    $sql = "SELECT client_key, expire_at, client_name FROM clientes WHERE client_key = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        die("Erro na preparação da query: " . $mysqli->error);
    }
    
    $stmt->bind_param("s", $apiKey);
    $stmt->execute();

    $stmt->bind_result($clientKey, $expireAt, $client_name);
    if ($stmt->fetch()) {
        $stmt->close();
        return [
            "client_key" => $clientKey,
            "expire_at" => $expireAt,
            "client_name" => $client_name
        ];
    } else {
        $stmt->close();
        return null;
    }
}

function isKeyExpired($expireAt) {
    return strtotime("now") >= strtotime($expireAt);
}
if (isset($data["api_key"]) && !empty($data["api_key"])) {

    $logUser = validateApiKey($data["api_key"], $mysqli);
    
    if (!$logUser) {
        http_response_code(403);
        echo json_encode(["error" => "Api Key Inválida"]);
        exit;
    }
    
    if (isKeyExpired($logUser["expire_at"])) {
        http_response_code(403);
        echo json_encode(["error" => "Api Key Expirada"]);
        exit;
    }
} else {
    http_response_code(403);
    echo json_encode(["error" => "Api Key Não Passado No Body"]);
    exit;
}


$action = $data["action"] ?? "";
$cidade = $data["cidade"] ?? "";
$estado = $data["estado"] ?? "";
$rua    = $data["rua"]    ?? '';

header('Content-Type: application/json');
if ($action === "buscaRua") {
    $infoFile = __DIR__ . "/info.json";
    $estadosValidos = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA',
        'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'
    ];
    if (!str_contains($rua, 'Rua')) {
        $rua = 'Rua '.$rua;
    }
    if (isset($estado) && !empty($estado)) {
        if (in_array($estado, $estadosValidos)) {
        } else if (file_exists($infoFile)) {
            $data = json_decode(file_get_contents($infoFile), true);
            if (isset($data["estados"]) && is_array($data["estados"])) {
                foreach ($data["estados"] as $d) {
                    foreach ($d as $chave => $valor) {
                        if (strtolower($estado) == strtolower($chave)) {
                            $estado = $valor;
                            break 2;
                        }
                    }
                }
            }else {
                echo "Formato inválido";
            }
        }
    }
    if (isset($cidade) and !empty($cidade) and isset($estado) and !empty($estado)){
        $query = "SELECT * FROM logradouro WHERE descricao_sem_numero = ? AND descricao_cidade = ? AND UF = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("sss", $rua, $cidade, $estado);
    } else if (isset($cidade) and !empty($cidade)) {
        $query = "SELECT * FROM logradouro WHERE descricao_sem_numero = ? AND descricao_cidade = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("ss", $rua, $cidade);
    } else if (isset($estado) and !empty($estado)) {
        $query = "SELECT * FROM logradouro WHERE descricao_sem_numero = ? AND UF = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("ss", $rua, $estado);
    } else {
        $query = "SELECT * FROM logradouro WHERE descricao_sem_numero = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("s", $rua);
    }
    
    $stmt->execute();
    $resultado = $stmt->get_result();
    if ($resultado->num_rows > 0) {
        http_response_code(200);
        while ($row = $resultado->fetch_assoc()) {
            $resultados[] = [
                "rua"     => $row["descricao_sem_numero"],
                "bairro"  => $row["descricao_bairro"],
                "cidade"  => $row["descricao_cidade"],
                "estado"  => $row["UF"],
                "cep"     => $row["CEP"]
            ];
        }
    } else {
        http_response_code(404);
        $resultados[] = ["mensagem" => "Nenhuma cidade encontada"];
    }
    echo json_encode($resultados);
} else if ($action === "buscaEstado") {
    if (isset($cidade) and !empty($cidade)) {
        $query = "SELECT * FROM cidade WHERE descricao = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("s", $cidade);
    }
    $stmt->execute();
    $resultado = $stmt->get_result();
    if ($resultado->num_rows > 0) {
        http_response_code(200);
        while ($row = $resultado->fetch_assoc()) {
            $resultados[] = [
                "Cidade: " => $row["descricao"],
                "Uf: "     => $row["uf"]
            ];
        }
    } else {
        http_response_code(404);
        $resultados[] = ["mensagem" => "Cidade não encontrada"];
    }
    echo json_encode($resultados);
} else {
    http_response_code(404);
    echo json_encode(["error" => "End Point não encontrado ou inválido"]);
    exit;
}
echo json_encode(["user" => $logUser["client_name"]]);
?>