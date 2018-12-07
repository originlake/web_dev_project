from django import forms

class DeleteUserForm(forms.Form):
    """
    A form that lets a user delete their account by entering correct password
    """
    password = forms.CharField(
        label="Password",
        strip=False,
        widget=forms.PasswordInput,
    )

    error_messages = {
        'password_incorrect': "Your old password was entered incorrectly. Please enter it again.",
    }

    def __init__(self, user, *args, **kwargs):
        self.user = user
        super().__init__(*args, **kwargs)

    def clean_password(self):
        """
        Validate password
        """
        password = self.cleaned_data["password"]
        if not self.user.check_password(password):
            raise forms.ValidationError(
                self.error_messages['password_incorrect'],
                code='password_incorrect',
            )
        return password

    def delete(self):
        password = self.cleaned_data["password"]
        self.user.delete()
