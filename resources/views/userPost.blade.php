<h1>add user</h1>
<form action="add" method="POST">
    @csrf
    <input type="text" name="name" placeholder="Enter name"><br>
    <input type="text" name="email" placeholder="Enter email"><br>
    <input type="text" name="password" placeholder="Enter password"><br>
    <button type="submit">submit</button><br>

</form>