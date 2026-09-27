@include('errors.simple', ['code' => 429, 'title' => 'Trop de tentatives', 'message' => 'Veuillez patienter quelques instants avant de recommencer.', 'retry' => true])
